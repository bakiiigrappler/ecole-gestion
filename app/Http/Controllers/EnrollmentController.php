<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\ParentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    /**
     * Afficher la liste des inscriptions
     */
    public function index(Request $request)
    {
        $query = Enrollment::with(['student', 'schoolClass.level', 'academicYear']);

        if ($annee = $request->input('academic_year')) {
            $query->where('academic_year_id', $annee);
        }

        if ($cycle = $request->input('cycle')) {
            $query->whereHas('schoolClass.level', fn ($q) => $q->where('cycle', $cycle));
        }

        if ($classe = $request->input('class')) {
            $query->where('class_id', $classe);
        }

        if ($statut = $request->input('status')) {
            $query->where('status', $statut);
        }

        // Situation de paiement : c'est le filtre qui sert au quotidien pour
        // retrouver les dossiers qui restent à encaisser.
        if ($paiement = $request->input('payment_status')) {
            $query->where('payment_status', $paiement);
        }

        if ($statutEleve = $request->input('student_status')) {
            $query->where('student_status', $statutEleve);
        }

        if ($statutCourant = $request->input('student_current_status')) {
            $query->whereHas('student', fn ($q) => $q->where('current_status', $statutCourant));
        }

        if ($type = $request->input('enrollment_type')) {
            if ($type === 'new') {
                $query->where('is_new_enrollment', true);
            } elseif ($type === 'reinscription') {
                $query->where('is_reinscription', true);
            }
        }

        // `like` est sensible à la casse sur PostgreSQL : une recherche « masson »
        // ne trouvait pas « Masson ». `ilike` rend le filtre réellement utilisable.
        if ($recherche = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($recherche) {
                $q->where('applicant_first_name', 'ilike', "%{$recherche}%")
                  ->orWhere('applicant_last_name', 'ilike', "%{$recherche}%")
                  ->orWhere('reinscription_student_id', 'ilike', "%{$recherche}%")
                  ->orWhere('receipt_number', 'ilike', "%{$recherche}%")
                  ->orWhereHas('student', fn ($sq) => $sq
                      ->where('student_id', 'ilike', "%{$recherche}%")
                      ->orWhere('first_name', 'ilike', "%{$recherche}%")
                      ->orWhere('last_name', 'ilike', "%{$recherche}%"));
            });
        }

        // Taille de page : celle demandee si elle est permise, sinon celle
        // reglee pour la plateforme.
        $parPage = \App\Support\ParametresPlateforme::pagination($request->input('per_page'));

        // `withQueryString()` : sans lui, passer à la page 2 perdait tous les filtres.
        $enrollments = $query->orderBy('enrollment_date', 'desc')
            ->paginate($parPage)
            ->withQueryString();

        return view('enrollments.index', array_merge([
            'enrollments' => $enrollments,
            'academicYears' => AcademicYear::orderBy('name', 'desc')->get(),
            'levels' => Level::active()->orderBy('order')->get(),
            'classes' => SchoolClass::with('level')->active()->orderBy('name')->get(),
        ], $this->statistiquesDesInscriptions()));
    }

    /**
     * Suggestions d'eleves pendant la frappe, pour une reinscription.
     *
     * Le champ imposait de connaitre le matricule exact puis de cliquer sur
     * « Rechercher ». On propose desormais les eleves au fil de la saisie, sur
     * le matricule comme sur le nom, en signalant ceux deja inscrits.
     */
    public function suggererEleves(Request $request)
    {
        $motif = trim((string) $request->input('q'));

        if (mb_strlen($motif) < 2) {
            return response()->json(['eleves' => []]);
        }

        $annee = $request->input('academic_year_id');

        $eleves = Student::query()
            ->where(fn ($q) => $q
                ->where('student_id', 'ilike', "%{$motif}%")
                ->orWhere('first_name', 'ilike', "%{$motif}%")
                ->orWhere('last_name', 'ilike', "%{$motif}%")
                ->orWhereRaw("(first_name || ' ' || last_name) ilike ?", ["%{$motif}%"]))
            ->with(['enrollments' => fn ($q) => $q->with(['schoolClass', 'academicYear'])
                ->orderByDesc('enrollment_date')])
            ->orderBy('last_name')->orderBy('first_name')
            ->limit(8)
            ->get();

        return response()->json([
            'eleves' => $eleves->map(function ($e) use ($annee) {
                $surCetteAnnee = $annee ? $e->enrollments->firstWhere('academic_year_id', (int) $annee) : null;
                $derniere = $e->enrollments->reject(fn ($i) => $annee && $i->academic_year_id == $annee)->first();

                return [
                    'matricule' => $e->student_id,
                    'nom' => $e->full_name,
                    'initiales' => mb_strtoupper(mb_substr($e->first_name, 0, 1).mb_substr($e->last_name, 0, 1)),
                    'derniere_classe' => $derniere?->schoolClass?->name,
                    'derniere_annee' => $derniere?->academicYear?->name,
                    'deja_inscrit' => $surCetteAnnee
                        ? ($surCetteAnnee->schoolClass->name ?? 'une classe')
                        : null,
                ];
            })->values(),
        ]);
    }

    /**
     * Rechercher un eleve par matricule, pour une reinscription.
     *
     * Le formulaire s'appuyait sur `/api/enrollments/search-student-for-reinscription`,
     * une route sans authentification qui divulgue une fiche eleve a quiconque
     * connait un matricule. Celle-ci vit dans la zone protegee.
     */
    public function rechercherEleve(Request $request)
    {
        $matricule = trim((string) $request->input('matricule'));

        if ($matricule === '') {
            return response()->json(['trouve' => false]);
        }

        $eleve = Student::with(['enrollments' => fn ($q) => $q
                ->with(['schoolClass.level', 'academicYear'])
                ->orderByDesc('enrollment_date')])
            ->where('student_id', $matricule)
            ->first();

        if (! $eleve) {
            return response()->json([
                'trouve' => false,
                'message' => 'Aucun élève ne porte le matricule '.$matricule.'.',
            ]);
        }

        $annee = $request->input('academic_year_id');
        $dejaInscrit = $annee
            ? $eleve->enrollments->firstWhere('academic_year_id', (int) $annee)
            : null;

        $derniere = $eleve->enrollments->reject(fn ($e) => $annee && $e->academic_year_id == $annee)->first();

        return response()->json([
            'trouve' => true,
            'eleve' => [
                'id' => $eleve->id,
                'matricule' => $eleve->student_id,
                'prenom' => $eleve->first_name,
                'nom' => $eleve->last_name,
                'naissance' => $eleve->date_of_birth?->format('Y-m-d'),
                'sexe' => $eleve->gender,
                'telephone' => $eleve->phone,
                'email' => $eleve->email,
                'adresse' => $eleve->address,
            ],
            'deja_inscrit' => $dejaInscrit ? [
                'classe' => $dejaInscrit->schoolClass->name ?? '—',
                'annee' => $dejaInscrit->academicYear->name ?? '—',
            ] : null,
            'precedente' => $derniere ? [
                'class_id' => $derniere->class_id,
                'classe' => $derniere->schoolClass->name ?? '—',
                'academic_year_id' => $derniere->academic_year_id,
                'annee' => $derniere->academicYear->name ?? '—',
            ] : null,
        ]);
    }

    /**
     * Frais applicables a un niveau, au format JSON.
     *
     * Le formulaire d'inscription faisait saisir le montant total a la main.
     * Il est desormais propose depuis le referentiel : frais obligatoires du
     * niveau d'un cote, options de l'autre.
     */
    public function fraisDuNiveau(Request $request)
    {
        $niveau = $request->filled('level_id') ? Level::find($request->input('level_id')) : null;

        if (! $niveau) {
            return response()->json(['obligatoires' => [], 'optionnels' => [], 'total' => 0]);
        }

        $frais = Fee::where('is_active', true)
            ->pourLeNiveau($niveau->id)
            ->orderByDesc('is_mandatory')
            ->orderBy('name')
            ->get(['id', 'name', 'amount', 'fee_type', 'frequency', 'is_mandatory', 'level_id']);

        $obligatoires = $frais->where('is_mandatory', true)->where('level_id', $niveau->id)->values();

        return response()->json([
            'niveau' => $niveau->name,
            'obligatoires' => $obligatoires,
            'optionnels' => $frais->reject(fn ($f) => $obligatoires->contains('id', $f->id))->values(),
            'total' => (float) $obligatoires->sum('amount'),
        ]);
    }

    /**
     * Chiffres de la liste des inscriptions.
     *
     * Les compteurs étaient calculés par une vingtaine de requêtes séparées —
     * une par cycle, par statut et par type. Trois agrégats groupés suffisent,
     * et les montants, eux, disent quelque chose d'utile.
     */
    private function statistiquesDesInscriptions(): array
    {
        $anneeCourante = AcademicYear::where('is_current', true)->value('id');

        $parCycle = DB::table('enrollments as e')
            ->join('classes as c', 'c.id', '=', 'e.class_id')
            ->join('levels as l', 'l.id', '=', 'c.level_id')
            ->when($anneeCourante, fn ($q) => $q->where('e.academic_year_id', $anneeCourante))
            ->selectRaw('l.cycle, count(*) as n')
            ->groupBy('l.cycle')
            ->pluck('n', 'cycle');

        $parPaiement = Enrollment::when($anneeCourante, fn ($q) => $q->where('academic_year_id', $anneeCourante))
            ->selectRaw('payment_status, count(*) as n')
            ->groupBy('payment_status')
            ->pluck('n', 'payment_status');

        $montants = Enrollment::when($anneeCourante, fn ($q) => $q->where('academic_year_id', $anneeCourante))
            ->selectRaw('sum(total_fees) as facture, sum(amount_paid) as encaisse, sum(balance_due) as reste')
            ->first();

        $facture = (float) ($montants->facture ?? 0);
        $encaisse = (float) ($montants->encaisse ?? 0);

        return [
            'totalEnrollments' => Enrollment::count(),
            'activeEnrollments' => Enrollment::where('status', 'active')->count(),
            'currentYearEnrollments' => (int) $parCycle->sum(),
            'enrollmentsByCycle' => [
                'preprimaire' => (int) ($parCycle['preprimaire'] ?? 0),
                'primaire' => (int) ($parCycle['primaire'] ?? 0),
                'college' => (int) ($parCycle['college'] ?? 0),
                'lycee' => (int) ($parCycle['lycee'] ?? 0),
            ],
            'pendingCount' => Enrollment::pendingStudentCreation()->count(),
            'recouvrement' => [
                'facture' => $facture,
                'encaisse' => $encaisse,
                'reste' => (float) ($montants->reste ?? 0),
                'taux' => $facture > 0 ? (int) round($encaisse / $facture * 100) : null,
                'soldes' => (int) ($parPaiement['completed'] ?? 0),
                'partiels' => (int) ($parPaiement['partial'] ?? 0),
                'impayes' => (int) ($parPaiement['pending'] ?? 0),
                'retards' => (int) ($parPaiement['overdue'] ?? 0),
            ],
        ];
    }

    /**
     * Afficher le formulaire d'inscription (nouveau workflow: inscription d'abord)
     */
    public function create()
    {
        $anneeCourante = AcademicYear::where('is_current', true)->first();

        return view('enrollments.create', [
            // L'annee du dossier reste modifiable, mais l'annee en cours est
            // proposee par defaut : c'est le cas courant.
            'academicYears' => AcademicYear::where('status', 'active')
                ->orWhere('is_current', true)
                ->orderByDesc('name')->get(),
            'anneeCourante' => $anneeCourante,
            'levels' => Level::active()->orderBy('order')->get(),
            'classes' => SchoolClass::with('level')->where('is_active', true)->orderBy('name')->get(),

            // Effectifs de l'annee proposee : le choix de classe montre la place
            // restante au lieu de la decouvrir au moment de l'enregistrement.
            'effectifs' => $anneeCourante
                ? DB::table('enrollments')
                    ->where('status', 'active')
                    ->where('academic_year_id', $anneeCourante->id)
                    ->selectRaw('class_id, count(*) as n')
                    ->groupBy('class_id')
                    ->pluck('n', 'class_id')
                : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $reinscription = $request->input('enrollment_type') === 'reinscription';

        $validated = $request->validate([
            'enrollment_type' => 'required|in:nouvelle,reinscription',

            // Identite de l'inscrit
            'applicant_first_name' => 'required|string|max:255',
            'applicant_last_name' => 'required|string|max:255',
            'applicant_date_of_birth' => 'required|date|before:today',
            'applicant_gender' => 'required|in:male,female',
            'applicant_phone' => 'nullable|string|max:255',
            'applicant_email' => 'nullable|email|max:255',
            'applicant_address' => 'required|string',

            // Responsable declare au depot du dossier
            'parent_first_name' => 'nullable|string|max:255',
            'parent_last_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email|max:255',
            'parent_relationship' => 'nullable|in:father,mother,guardian,other',

            // Affectation
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'enrollment_date' => 'required|date',
            'notes' => 'nullable|string',

            // Reinscription
            'reinscription_student_id' => 'required_if:enrollment_type,reinscription|nullable|string|max:255',
            'student_status' => 'required|in:nouveau,redoublant,passant',
            'previous_class_id' => 'nullable|exists:classes,id',
            'previous_academic_year_id' => 'nullable|exists:academic_years,id',
            'previous_year_result' => 'nullable|in:admis,redouble,non_applicable',
            'previous_year_average' => 'nullable|numeric|min:0|max:20',
            'status_comments' => 'nullable|string',

            // Reglement
            'total_fees' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0|lte:total_fees',
            'payment_method' => 'nullable|in:cash,bank_transfer,check,mobile_money,other',
            'payment_reference' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string',
            'payment_due_date' => 'nullable|date|after_or_equal:enrollment_date',
            'mobile_money_provider' => 'nullable|required_if:payment_method,mobile_money|in:airtel,moov',
            'mobile_money_number' => 'nullable|required_if:payment_method,mobile_money|string|regex:/^0[67][0-9]{7}$/',
        ], [
            'applicant_date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd’hui.',
            'amount_paid.lte' => 'Le montant versé ne peut pas dépasser le total à payer.',
            'reinscription_student_id.required_if' => 'Indiquez le matricule de l’élève à réinscrire.',
            'mobile_money_number.regex' => 'Le numéro doit contenir 9 chiffres et commencer par 06 (Moov) ou 07 (Airtel).',
        ]);

        /*
         * `$validated['payment_method']` etait lu sans garde : quand aucun moyen
         * n'etait choisi, la cle n'existait pas et la page tombait sur une
         * « Undefined array key ».
         */
        $moyen = $validated['payment_method'] ?? null;

        if ($moyen === 'mobile_money') {
            $operateur = $validated['mobile_money_provider'] ?? null;
            $numero = $validated['mobile_money_number'] ?? '';

            $prefixe = $operateur === 'airtel' ? '07' : '06';

            if (! str_starts_with($numero, $prefixe)) {
                return $this->echecInscription(
                    $request,
                    ['mobile_money_number' => 'Un numéro '.($operateur === 'airtel' ? 'Airtel' : 'Moov').' commence par '.$prefixe.'.'],
                    422
                );
            }
        }

        // Un eleve deja inscrit sur l'annee ne peut pas l'etre deux fois.
        $eleveExistant = $reinscription
            ? Student::where('student_id', $validated['reinscription_student_id'])->first()
            : null;

        if ($reinscription && ! $eleveExistant) {
            return $this->echecInscription(
                $request,
                ['reinscription_student_id' => 'Aucun élève ne porte le matricule '.$validated['reinscription_student_id'].'.'],
                422
            );
        }

        if ($eleveExistant) {
            $deja = Enrollment::with('schoolClass')
                ->where('student_id', $eleveExistant->id)
                ->where('academic_year_id', $validated['academic_year_id'])
                ->first();

            if ($deja) {
                return $this->echecInscription(
                    $request,
                    ['reinscription_student_id' => 'Cet élève est déjà inscrit cette année en '.($deja->schoolClass->name ?? 'classe inconnue').'.'],
                    422
                );
            }
        } else {
            // Nouvelle inscription : on se fie au nom et a la date de naissance.
            $doublon = Enrollment::where('academic_year_id', $validated['academic_year_id'])
                ->whereRaw('lower(applicant_first_name) = ?', [mb_strtolower($validated['applicant_first_name'])])
                ->whereRaw('lower(applicant_last_name) = ?', [mb_strtolower($validated['applicant_last_name'])])
                ->where('applicant_date_of_birth', $validated['applicant_date_of_birth'])
                ->exists();

            if ($doublon) {
                return $this->echecInscription(
                    $request,
                    ['applicant_last_name' => 'Un dossier existe déjà pour cette personne sur cette année scolaire.'],
                    422
                );
            }
        }

        // Capacite de la classe visee.
        $classe = SchoolClass::find($validated['class_id']);

        if (! $classe->hasAvailablePlaces($validated['academic_year_id'])) {
            return $this->echecInscription(
                $request,
                ['class_id' => 'La classe '.$classe->name.' est complète ('.$classe->getEnrolledStudentsCount($validated['academic_year_id']).'/'.$classe->capacity.').'],
                422
            );
        }

        DB::beginTransaction();
        try {
            $donnees = $validated;
            unset($donnees['enrollment_type']);

            $donnees['status'] = 'active';
            $donnees['is_reinscription'] = $reinscription;
            $donnees['is_new_enrollment'] = ! $reinscription;
            $donnees['balance_due'] = $validated['total_fees'] - $validated['amount_paid'];
            $donnees['previous_year_result'] = $validated['previous_year_result'] ?? 'non_applicable';

            // Une reinscription pointe deja sur un eleve ; une nouvelle
            // inscription attend qu'on le cree depuis le dossier.
            $donnees['student_id'] = $eleveExistant?->id;
            $donnees['enrollment_status'] = $eleveExistant ? 'student_created' : 'pending';

            $enrollment = Enrollment::create($donnees);

            $enrollment->updatePaymentStatus();
            $enrollment->generateReceiptNumber();
            $enrollment->generateEnrollmentCode();
            $enrollment->generatePaymentReference();
            $enrollment->save();

            if ($eleveExistant) {
                // Reinscription : on ne remplace une coordonnee que si le
                // dossier en apporte une, pour ne pas effacer l'existante.
                $eleveExistant->update([
                    'phone' => $validated['applicant_phone'] ?: $eleveExistant->phone,
                    'email' => $validated['applicant_email'] ?: $eleveExistant->email,
                    'address' => $validated['applicant_address'] ?: $eleveExistant->address,
                ]);
                $eleveExistant->updateEnrollmentStats();
            }

            DB::commit();

            $message = $reinscription
                ? 'Réinscription enregistrée. Les informations de l’élève ont été mises à jour.'
                : 'Inscription enregistrée. Créez maintenant le profil de l’élève.';

            // Le formulaire est un formulaire classique : lui renvoyer du JSON
            // affichait un bloc de code a l'ecran.
            if (! $request->expectsJson()) {
                return redirect()
                    ->route($eleveExistant ? 'enrollments.show' : 'enrollments.create-student', $enrollment->id)
                    ->with('success', $message);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'enrollment' => $enrollment->load(['schoolClass.level', 'academicYear', 'student']),
                'show_student_creation' => ! $eleveExistant,
                'is_reinscription' => $reinscription,
                'receipt_number' => $enrollment->receipt_number,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur lors de l enregistrement de l inscription', ['erreur' => $e->getMessage()]);

            if (! $request->expectsJson()) {
                return redirect()->back()->withInput()
                    ->with('error', 'Erreur lors de l’enregistrement de l’inscription.');
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refus d'une inscription : redirection avec l'erreur pour un formulaire,
     * JSON pour les appels programmatiques.
     */
    private function echecInscription(Request $request, array $erreurs, int $code)
    {
        if (! $request->expectsJson()) {
            return redirect()->back()->withInput()->withErrors($erreurs);
        }

        return response()->json([
            'success' => false,
            'message' => implode(' ', $erreurs),
        ], $code);
    }

    /**
     * Afficher les détails d'une inscription
     */
    public function show(Enrollment $enrollment)
    {
        $enrollment->load([
            'student.parents',
            'schoolClass.level',
            'academicYear',
            'enrollmentFees.fee',
            'payments' => fn ($q) => $q->orderByDesc('paid_at')->orderByDesc('created_at'),
        ]);

        // `previous_class_id` et `previous_academic_year_id` n'ont pas de relation
        // sur le modele : on les resout ici pour montrer d'ou vient l'eleve.
        $classePrecedente = $enrollment->previous_class_id
            ? SchoolClass::with('level')->find($enrollment->previous_class_id)
            : null;

        $anneePrecedente = $enrollment->previous_academic_year_id
            ? AcademicYear::find($enrollment->previous_academic_year_id)
            : null;

        // Parcours de l'eleve : les autres inscriptions du meme dossier.
        $autresInscriptions = $enrollment->student_id
            ? Enrollment::with(['schoolClass.level', 'academicYear'])
                ->where('student_id', $enrollment->student_id)
                ->where('id', '!=', $enrollment->id)
                ->orderByDesc('enrollment_date')
                ->get()
            : collect();

        return view('enrollments.show', compact(
            'enrollment', 'classePrecedente', 'anneePrecedente', 'autresInscriptions'
        ));
    }

    /**
     * Afficher le formulaire de modification d'inscription
     */
    public function edit(Enrollment $enrollment)
    {
        $enrollment->load(['student', 'schoolClass.level', 'academicYear', 'payments']);

        /*
         * Annees proposees : celles ouvertes, plus celle du dossier meme si elle
         * est cloturee. Sans cela, ouvrir une ancienne inscription vidait le
         * champ et l'enregistrement la basculait silencieusement d'annee.
         */
        $academicYears = AcademicYear::where('status', 'active')
            ->orWhere('id', $enrollment->academic_year_id)
            ->orderByDesc('name')
            ->get();

        $classes = SchoolClass::with('level')->active()->orderBy('name')->get();

        // Effectif de chaque classe : on ne change pas d'affectation a l'aveugle.
        $effectifs = DB::table('enrollments')
            ->where('status', 'active')
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->selectRaw('class_id, count(*) as n')
            ->groupBy('class_id')
            ->pluck('n', 'class_id');

        return view('enrollments.edit', compact(
            'enrollment', 'academicYears', 'classes', 'effectifs'
        ) + ['levels' => Level::active()->orderBy('order')->get()]);
    }

    /**
     * Mettre à jour une inscription
     */
    public function update(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'enrollment_date' => 'required|date',
            // Contrainte CHECK de la base : « inactive » et « graduated » etaient
            // proposes par le formulaire alors que PostgreSQL les refuse.
            'status' => 'required|in:active,completed,transferred,dropped',
            'student_status' => 'nullable|in:nouveau,redoublant,passant',
            // `payment_status` n'est pas modifiable a la main : il decoule des
            // montants encaisses. Le laisser saisir creerait un dossier affiche
            // « solde » avec un reste du.
            'payment_due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        /*
         * Une classe ne peut recevoir deux fois le meme eleve sur la meme annee :
         * le doublon passait sans controle et faussait les effectifs.
         */
        if ($enrollment->student_id) {
            $doublon = Enrollment::where('student_id', $enrollment->student_id)
                ->where('academic_year_id', $validated['academic_year_id'])
                ->where('id', '!=', $enrollment->id)
                ->exists();

            if ($doublon) {
                $message = 'Cet élève a déjà une inscription sur cette année scolaire.';

                if (! $request->expectsJson()) {
                    return redirect()->back()->withInput()
                        ->withErrors(['academic_year_id' => $message]);
                }

                return response()->json(['success' => false, 'message' => $message], 422);
            }
        }

        DB::beginTransaction();
        try {
            $enrollment->update(array_filter(
                $validated,
                fn ($v) => $v !== null
            ));

            DB::commit();

            // Le formulaire de modification est un formulaire classique : lui
            // renvoyer du JSON affichait un bloc de code a l'ecran.
            if (! $request->expectsJson()) {
                return redirect()->route('enrollments.show', $enrollment->id)
                    ->with('success', 'Inscription mise à jour avec succès.');
            }

            return response()->json([
                'success' => true,
                'message' => 'Inscription mise à jour avec succès!',
                'enrollment' => $enrollment->load(['student', 'schoolClass.level', 'academicYear'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur lors de la mise a jour de l inscription', [
                'enrollment_id' => $enrollment->id,
                'erreur' => $e->getMessage(),
            ]);

            if (! $request->expectsJson()) {
                return redirect()->back()->withInput()
                    ->with('error', 'Erreur lors de la mise à jour de l’inscription.');
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une inscription
     */
    public function destroy(Request $request, Enrollment $enrollment)
    {
        /*
         * Rien ne protegeait la suppression : un dossier deja encaisse
         * disparaissait en laissant ses paiements orphelins, sans trace du
         * motif de l'encaissement.
         */
        $paiements = $enrollment->payments()->count();

        if ($paiements > 0) {
            $message = 'Impossible de supprimer cette inscription : '.$paiements.' paiement(s) y sont rattachés'
                .' ('.number_format((float) $enrollment->amount_paid, 0, ',', ' ').' F encaissés).'
                .' Passez-la plutôt au statut « transférée » ou « inactive ».';

            if (! $request->expectsJson()) {
                return redirect()->route('enrollments.show', $enrollment->id)->with('error', $message);
            }

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        try {
            $enrollment->delete();

            if (! $request->expectsJson()) {
                return redirect()->route('enrollments.index')
                    ->with('success', 'Inscription supprimée avec succès.');
            }

            return response()->json([
                'success' => true,
                'message' => 'Inscription supprimée avec succès!'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l inscription', [
                'enrollment_id' => $enrollment->id,
                'erreur' => $e->getMessage(),
            ]);

            if (! $request->expectsJson()) {
                return redirect()->route('enrollments.index')
                    ->with('error', 'Erreur lors de la suppression de l’inscription.');
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher le formulaire de réinscription
     */
    public function reEnroll(Student $student)
    {
        // Récupérer la dernière inscription de l'étudiant
        $lastEnrollment = $student->enrollments()
                                ->with(['schoolClass.level', 'academicYear'])
                                ->orderBy('enrollment_date', 'desc')
                                ->first();
        
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();
        $levels = Level::active()->orderBy('order')->get();
        $classes = SchoolClass::with('level')->active()->get();
        
        return view('enrollments.re-enroll', compact('student', 'lastEnrollment', 'currentAcademicYear', 'levels', 'classes'));
    }

    /**
     * Traiter la réinscription
     */
    public function processReEnrollment(Request $request, Student $student)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'enrollment_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        // Vérifier si l'étudiant n'est pas déjà inscrit pour cette année
        $existingEnrollment = $student->enrollments()
                                    ->where('academic_year_id', $validated['academic_year_id'])
                                    ->first();

        if ($existingEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'L\'élève est déjà inscrit pour cette année scolaire.'
            ], 422);
        }

        // Vérifier la capacité de la classe
        $selectedClass = SchoolClass::find($validated['class_id']);
        if (!$selectedClass->hasAvailablePlaces($validated['academic_year_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette classe est complète. Aucune place disponible.',
                'class_info' => [
                    'name' => $selectedClass->name,
                    'capacity' => $selectedClass->capacity,
                    'enrolled_count' => $selectedClass->getEnrolledStudentsCount($validated['academic_year_id']),
                    'available_places' => $selectedClass->getAvailablePlaces($validated['academic_year_id'])
                ]
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Créer la nouvelle inscription
            $enrollment = $student->enrollments()->create([
                'class_id' => $validated['class_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'enrollment_date' => $validated['enrollment_date'],
                'status' => 'active',
                'notes' => $validated['notes'] ?? 'Réinscription'
            ]);
            
            // Mettre à jour le statut de l'étudiant
            $student->update(['status' => 'active']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Réinscription effectuée avec succès!',
                'enrollment' => $enrollment->load(['student', 'schoolClass.level', 'academicYear'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les étudiants non inscrits pour l'année courante
     */
    public function getUnEnrolledStudents()
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        
        if (!$currentYear) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune année scolaire courante définie.'
            ]);
        }

        $unEnrolledStudents = Student::where('status', 'active')
                                   ->whereDoesntHave('enrollments', function($q) use ($currentYear) {
                                       $q->where('academic_year_id', $currentYear->id);
                                   })
                                   ->with(['enrollments.schoolClass.level', 'enrollments.academicYear'])
                                   ->orderBy('last_name')
                                   ->get();

        return response()->json([
            'success' => true,
            'students' => $unEnrolledStudents,
            'current_year' => $currentYear
        ]);
    }

    /**
     * Rechercher des inscriptions
     */
    public function search(Request $request)
    {
        $query = Enrollment::with(['student', 'schoolClass.level', 'academicYear']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('academic_year') && $request->academic_year) {
            $query->where('academic_year_id', $request->academic_year);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $enrollments = $query->get();

        return response()->json($enrollments);
    }

    /**
     * Afficher le formulaire de création d'élève depuis une inscription
     */
    public function showStudentCreationForm(Enrollment $enrollment)
    {
        if (!$enrollment->canCreateStudent()) {
            return redirect()->route('enrollments.index')
                           ->with('error', 'Cette inscription ne permet pas la création d\'un élève.');
        }

        /*
         * Un `Log::info` de mise au point appelait ici `get_class($relation ?? null)`.
         * En PHP 8, `get_class(null)` leve une TypeError : un dossier sans classe
         * ou sans annee faisait tomber la page. Il est retire.
         */
        $enrollment->load(['schoolClass.level', 'academicYear']);

        // Un responsable portant le meme telephone ou le meme nom existe
        // peut-etre deja : on le propose plutot que d'en creer un doublon.
        $responsableExistant = null;

        if ($enrollment->parent_last_name || $enrollment->parent_phone) {
            $responsableExistant = ParentModel::query()
                ->when($enrollment->parent_phone, fn ($q) => $q->where('phone', $enrollment->parent_phone))
                ->when(! $enrollment->parent_phone, fn ($q) => $q
                    ->whereRaw('lower(first_name) = ?', [mb_strtolower((string) $enrollment->parent_first_name)])
                    ->whereRaw('lower(last_name) = ?', [mb_strtolower((string) $enrollment->parent_last_name)]))
                ->first();
        }

        return view('enrollments.create-student', [
            'enrollment' => $enrollment,
            'matriculePropose' => Student::generateStudentId(),
            'responsableExistant' => $responsableExistant,
        ]);
    }

    /**
     * Créer un élève depuis une inscription
     */
    public function createStudentFromEnrollment(Request $request, Enrollment $enrollment)
    {
        if (! $enrollment->canCreateStudent()) {
            $message = 'Cette inscription ne permet pas la création d\'un élève.';

            if (! $request->expectsJson()) {
                return redirect()->route('enrollments.show', $enrollment->id)->with('error', $message);
            }

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $valide = $request->validate([
            // Le matricule est propose automatiquement mais reste modifiable :
            // l'ancienne version ecrasait toujours la saisie sans le dire.
            'student_id' => 'nullable|string|max:50|unique:students,student_id',
            'place_of_birth' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'medical_conditions' => 'nullable|string',
            'fitness_status' => 'nullable|in:apte,inapte',
            'unfitness_reason' => 'nullable|required_if:fitness_status,inapte|string|max:1000',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            // Responsable : cree depuis le dossier, ou rattache parmi les parents
            // existants. L'ancienne version n'en creait aucun.
            'responsable' => 'nullable|in:aucun,nouveau,existant',
            'parent_id' => 'nullable|required_if:responsable,existant|exists:parents,id',
            'lien' => 'nullable|in:father,mother,guardian,other',
        ], [
            'student_id.unique' => 'Ce matricule est déjà attribué à un autre élève.',
            'parent_id.required_if' => 'Choisissez le responsable à rattacher.',
            'unfitness_reason.required_if' => 'Précisez le motif de l’inaptitude.',
        ]);

        DB::beginTransaction();
        try {
            $donnees = $enrollment->getStudentDataForCreation();
            $donnees['student_id'] = $valide['student_id'] ?: Student::generateStudentId();
            $donnees['place_of_birth'] = $valide['place_of_birth'] ?? null;
            $donnees['emergency_contact'] = $valide['emergency_contact'] ?? null;
            $donnees['medical_conditions'] = $valide['medical_conditions'] ?? null;
            // Coordonnees du dossier : elles deviennent celles de l'eleve.
            $donnees['phone'] = $enrollment->applicant_phone;
            $donnees['email'] = $enrollment->applicant_email;

            $donnees['fitness_status'] = $valide['fitness_status'] ?? 'apte';
            $donnees['unfitness_reason'] = ($donnees['fitness_status'] === 'inapte')
                ? ($valide['unfitness_reason'] ?? null)
                : null;

            if ($request->hasFile('photo')) {
                $donnees['photo'] = $request->file('photo')->store('students/photos', 'public');
            }

            $eleve = Student::create($donnees);

            $this->rattacherLeResponsable($eleve, $enrollment, $valide);

            $enrollment->markAsStudentCreated($eleve->id);

            DB::commit();

            $message = 'Élève créé et rattaché à son inscription.';

            if (! $request->expectsJson()) {
                return redirect()->route('students.show', $eleve->id)->with('success', $message);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'student' => $eleve,
                'enrollment' => $enrollment->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur lors de la creation de l eleve depuis l inscription', [
                'enrollment_id' => $enrollment->id,
                'erreur' => $e->getMessage(),
            ]);

            if (! $request->expectsJson()) {
                return redirect()->back()->withInput()
                    ->with('error', 'Erreur lors de la création de l\'élève.');
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rattache un responsable au nouvel eleve : un parent existant, ou un parent
     * cree a partir des coordonnees saisies au depot du dossier.
     */
    private function rattacherLeResponsable(Student $eleve, Enrollment $enrollment, array $valide): void
    {
        $choix = $valide['responsable'] ?? 'aucun';

        if ($choix === 'aucun') {
            return;
        }

        $lien = $valide['lien'] ?? $enrollment->parent_relationship ?? 'guardian';

        $parent = $choix === 'existant'
            ? ParentModel::find($valide['parent_id'])
            : ParentModel::create([
                'first_name' => $enrollment->parent_first_name,
                'last_name' => $enrollment->parent_last_name,
                'phone' => $enrollment->parent_phone,
                'email' => $enrollment->parent_email,
                'address' => $enrollment->applicant_address,
                'gender' => match ($lien) {
                    'father' => 'male',
                    'mother' => 'female',
                    default => null,
                },
            ]);

        if (! $parent) {
            return;
        }

        $eleve->parents()->syncWithoutDetaching([
            $parent->id => [
                'relationship_type' => $lien,
                // Premier responsable rattache : il devient le contact principal.
                'is_primary_contact' => true,
                'lives_with_student' => true,
                'can_pickup' => true,
            ],
        ]);
    }

    public function pendingStudentCreations()
    {
        $pendingEnrollments = Enrollment::pendingStudentCreation()
                                      ->with(['schoolClass.level', 'academicYear'])
                                      ->orderBy('enrollment_date', 'desc')
                                      ->paginate(15);

        return view('enrollments.pending-students', compact('pendingEnrollments'));
    }

    /**
     * Marquer une inscription comme "en attente" (pas de création d'élève pour l'instant)
     */
    public function markAsPending(Enrollment $enrollment)
    {
        if ($enrollment->canCreateStudent()) {
            $enrollment->update(['enrollment_status' => 'pending']);
            
            return response()->json([
                'success' => true,
                'message' => 'Inscription marquée comme en attente.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Cette inscription ne peut pas être marquée comme en attente.'
        ], 422);
    }

    /**
     * L'eleve d'une inscription, quel que soit son etat d'avancement.
     *
     * Une inscription porte les coordonnees saisies au guichet (`applicant_*`)
     * avant meme qu'une fiche eleve existe ; une fois l'eleve cree, c'est sa
     * fiche qui fait foi. Les documents doivent lire les deux.
     */
    private function eleveDeLInscription(Enrollment $enrollment): array
    {
        $eleve = $enrollment->student;

        return [
            'nom' => $eleve->last_name ?? $enrollment->applicant_last_name,
            'prenom' => $eleve->first_name ?? $enrollment->applicant_first_name,
            'matricule' => $eleve->student_id ?? null,
            'naissance' => $eleve->date_of_birth ?? $enrollment->applicant_date_of_birth,
            'lieu' => $eleve->place_of_birth ?? null,
            'sexe' => $eleve->gender ?? $enrollment->applicant_gender,
            'adresse' => $eleve->address ?? $enrollment->applicant_address,
            'telephone' => $eleve->phone ?? $enrollment->applicant_phone,
            'photo' => $eleve->photo ?? null,
        ];
    }

    /**
     * Le responsable a porter sur les documents.
     *
     * Celui saisi sur l'inscription d'abord ; a defaut le parent rattache a
     * l'eleve, en preferant le contact principal. Le recu affichait un bloc
     * vide des que l'inscription ne portait pas elle-meme ces champs, alors
     * que l'eleve avait bien deux parents a son dossier.
     */
    private function responsableDeLInscription(Enrollment $enrollment): ?array
    {
        $liens = [
            'father' => 'Père', 'mother' => 'Mère', 'guardian' => 'Tuteur',
            'tutor' => 'Tuteur', 'brother' => 'Frère', 'sister' => 'Sœur',
            'uncle' => 'Oncle', 'aunt' => 'Tante', 'other' => 'Autre',
        ];

        $traduire = fn ($v) => $liens[mb_strtolower((string) $v)] ?? (trim((string) $v) ?: 'Non précisé');

        if ($enrollment->parent_last_name || $enrollment->parent_first_name) {
            return [
                'nom' => trim($enrollment->parent_last_name.' '.$enrollment->parent_first_name),
                'lien' => $traduire($enrollment->parent_relationship),
                'telephone' => $enrollment->parent_phone,
                'email' => $enrollment->parent_email,
            ];
        }

        $parent = $enrollment->student?->parents
            ?->sortByDesc(fn ($p) => (int) ($p->pivot->is_primary_contact ?? 0))
            ->first();

        if (! $parent) {
            return null;
        }

        return [
            'nom' => trim($parent->last_name.' '.$parent->first_name),
            'lien' => $traduire($parent->pivot->relationship_type ?? null),
            'telephone' => $parent->phone,
            'email' => $parent->email,
        ];
    }

    /** Ce que les deux documents ont en commun. */
    private function pieceDInscription(Enrollment $enrollment): array
    {
        $enrollment->load(['schoolClass.level', 'academicYear', 'student.parents']);

        return [
            'enrollment' => $enrollment,
            'schoolSettings' => \App\Models\SchoolSettings::getSettings(),
            'schoolName' => \App\Helpers\SchoolHelper::getSchoolNameByLevel($enrollment->schoolClass->level),
            'eleve' => $this->eleveDeLInscription($enrollment),
            'responsable' => $this->responsableDeLInscription($enrollment),
        ];
    }

    /**
     * Le recu d'inscription, tel qu'il s'imprime.
     */
    public function generateReceipt(Enrollment $enrollment)
    {
        if (! $enrollment->receipt_number) {
            $enrollment->generateReceiptNumber();
        }

        return view('enrollments.receipt', $this->pieceDInscription($enrollment));
    }

    /**
     * Le telechargement mene au meme document.
     *
     * Le PDF etait rendu par dompdf a partir d'un second gabarit : deux
     * documents a tenir a jour, et une mise en page qui divergeait de celle
     * affichee. Le document se photographie desormais tel qu'il est vu.
     */
    public function downloadReceipt(Enrollment $enrollment)
    {
        return redirect()->route('enrollments.receipt', $enrollment->id);
    }

    /**
     * L'autorisation d'entree, avec son code de verification.
     */
    public function downloadEntryAuthorization(Enrollment $enrollment)
    {
        if (! $enrollment->enrollment_code) {
            $enrollment->generateEnrollmentCode();
        }

        return view('enrollments.entry-authorization', $this->pieceDInscription($enrollment));
    }


    /**
     * Exporter les inscriptions
     */
    public function export(Request $request)
    {
        // Cette méthode pourra être étendue pour exporter en CSV/Excel
        $enrollments = Enrollment::with(['student', 'schoolClass.level', 'academicYear'])
                                ->when($request->academic_year, function($q, $year) {
                                    return $q->where('academic_year_id', $year);
                                })
                                ->get();

        return response()->json([
            'success' => true,
            'data' => $enrollments,
            'message' => 'Données exportées avec succès!'
        ]);
    }
    
    /**
     * Vérifier le statut d'un élève lors de la réinscription (API)
     */
    public function checkStudentStatus(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|string',
                'class_id' => 'required|exists:school_classes,id',
                'academic_year_id' => 'required|exists:academic_years,id'
            ]);
            
            $studentMatricule = $request->student_id;
            $classId = $request->class_id;
            $academicYearId = $request->academic_year_id;
            
            // Trouver l'élève par son matricule
            $student = Student::where('student_id', $studentMatricule)->first();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun élève trouvé avec ce matricule',
                    'student_status' => 'nouveau',
                    'status_comments' => 'Matricule non trouvé - traité comme nouvel élève'
                ]);
            }
            
            // Trouver la dernière inscription de l'élève
            $lastEnrollment = Enrollment::where('student_id', $student->id)
                ->orderBy('academic_year_id', 'desc')
                ->with(['schoolClass.level', 'academicYear'])
                ->first();
            
            if (!$lastEnrollment) {
                return response()->json([
                    'success' => true,
                    'student_status' => 'nouveau',
                    'status_comments' => 'Aucune inscription précédente trouvée',
                    'student_info' => [
                        'first_name' => $student->first_name,
                        'last_name' => $student->last_name,
                        'student_id' => $student->student_id
                    ]
                ]);
            }
            
            // Calculer la moyenne de l'année précédente
            $previousAverage = $this->calculateStudentAverageForYear($student->id, $lastEnrollment->academic_year_id);
            
            // Déterminer si l'élève passe ou redouble (moyenne >= 10 pour passer)
            $hasPassed = $previousAverage >= 10;
            
            // Récupérer la classe actuelle sélectionnée
            $currentClass = SchoolClass::with('level')->find($classId);
            
            $studentStatus = 'nouveau';
            $previousYearResult = 'non_applicable';
            $statusComments = '';
            
            if ($hasPassed) {
                $previousYearResult = 'admis';
                
                // Vérifier si l'élève s'inscrit dans la classe suivante
                $isNextClass = $this->isNextClass($lastEnrollment->schoolClass, $currentClass);
                
                if ($isNextClass) {
                    $studentStatus = 'passant';
                    $statusComments = "Admis avec moyenne de {$previousAverage}/20 - Passage en classe supérieure";
                } else {
                    $studentStatus = 'nouveau';
                    $statusComments = "Admis mais inscription dans une classe différente";
                }
            } else {
                $previousYearResult = 'redouble';
                $studentStatus = 'redoublant';
                $statusComments = "Moyenne insuffisante ({$previousAverage}/20) - Redoublement";
            }
            
            // Mettre à jour les statistiques de l'élève
            $student->updateEnrollmentStats();
            
            return response()->json([
                'success' => true,
                'student_status' => $studentStatus,
                'previous_class_id' => $lastEnrollment->class_id,
                'previous_academic_year_id' => $lastEnrollment->academic_year_id,
                'previous_year_result' => $previousYearResult,
                'previous_year_average' => $previousAverage,
                'status_comments' => $statusComments,
                'student_info' => [
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'student_id' => $student->student_id,
                    'current_status' => $student->current_status,
                    'total_enrollments' => $student->total_enrollments,
                    'total_redoublements' => $student->total_redoublements,
                    'has_been_enrolled' => $student->has_been_enrolled,
                    'last_enrollment_date' => $student->last_enrollment_date ? $student->last_enrollment_date->format('d/m/Y') : null
                ],
                'previous_class' => $lastEnrollment->schoolClass->name ?? 'N/A',
                'current_class' => $currentClass->name ?? 'N/A',
                'enrollment_history' => $student->getEnrollmentHistory()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du statut de l\'élève: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la vérification du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculer la moyenne générale d'un élève pour une année scolaire
     */
    private function calculateStudentAverageForYear($studentId, $academicYearId)
    {
        $grades = \App\Models\Grade::where('student_id', $studentId)
            ->whereHas('subject', function($q) use ($academicYearId) {
                $q->whereHas('schedules', function($sq) use ($academicYearId) {
                    $sq->where('academic_year_id', $academicYearId);
                });
            })
            ->get();
        
        if ($grades->isEmpty()) {
            return 0;
        }
        
        $totalWeightedScore = 0;
        $totalCoefficients = 0;
        
        foreach ($grades as $grade) {
            $subject = $grade->subject;
            $coefficient = $subject ? ($subject->coefficient ?? 1) : 1;
            
            $totalWeightedScore += $grade->score * $coefficient;
            $totalCoefficients += $coefficient;
        }
        
        return $totalCoefficients > 0 ? round($totalWeightedScore / $totalCoefficients, 2) : 0;
    }
    
    /**
     * Vérifier si la classe actuelle est la classe suivante de la classe précédente
     */
    private function isNextClass($previousClass, $currentClass)
    {
        if (!$previousClass || !$currentClass) {
            return false;
        }
        
        // Charger les niveaux si nécessaire
        if (!$previousClass->relationLoaded('level')) {
            $previousClass->load('level');
        }
        if (!$currentClass->relationLoaded('level')) {
            $currentClass->load('level');
        }
        
        $previousLevel = $previousClass->level;
        $currentLevel = $currentClass->level;
        
        if (!$previousLevel || !$currentLevel) {
            return false;
        }
        
        // Vérifier si le cycle est le même
        if ($previousLevel->cycle !== $currentLevel->cycle) {
            // Peut-être un passage de cycle (ex: préprimaire -> primaire)
            return false;
        }
        
        // Vérifier si l'ordre du niveau actuel est supérieur de 1
        return ($currentLevel->order == $previousLevel->order + 1);
    }
    
    /**
     * Rechercher un élève et suggérer la classe pour réinscription (API)
     */
    public function searchStudentForReinscription(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|string',
                'academic_year_id' => 'required|exists:academic_years,id'
            ]);
            
            $studentMatricule = $request->student_id;
            $academicYearId = $request->academic_year_id;
            
            // Trouver l'élève par son matricule
            $student = Student::where('student_id', $studentMatricule)->first();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun élève trouvé avec ce matricule'
                ]);
            }
            
            // Mettre à jour les statistiques de l'élève
            $student->updateEnrollmentStats();
            
            // Vérifier si l'élève est déjà inscrit pour l'année en cours
            $existingEnrollment = Enrollment::where('student_id', $student->id)
                ->where('academic_year_id', $academicYearId)
                ->first();
            
            if ($existingEnrollment) {
                return response()->json([
                    'success' => false,
                    'already_enrolled' => true,
                    'message' => 'Cet élève est déjà inscrit pour cette année scolaire',
                    'enrollment_info' => [
                        'class' => $existingEnrollment->schoolClass->name ?? 'N/A',
                        'enrollment_date' => $existingEnrollment->enrollment_date->format('d/m/Y'),
                        'student_status' => $existingEnrollment->student_status
                    ]
                ], 422);
            }
            
            // Trouver la dernière inscription de l'élève
            $lastEnrollment = Enrollment::where('student_id', $student->id)
                ->orderBy('academic_year_id', 'desc')
                ->with(['schoolClass.level', 'academicYear'])
                ->first();
            
            if (!$lastEnrollment) {
                return response()->json([
                    'success' => true,
                    'student_status' => 'nouveau',
                    'status_comments' => 'Aucune inscription précédente trouvée',
                    'student_info' => [
                        'first_name' => $student->first_name,
                        'last_name' => $student->last_name,
                        'student_id' => $student->student_id,
                        'date_of_birth' => $student->date_of_birth->format('Y-m-d'),
                        'gender' => $student->gender,
                        'phone' => $student->emergency_contact ?? '',
                        'email' => '',
                        'address' => $student->address ?? ''
                    ]
                ]);
            }
            
            // Calculer la moyenne de l'année précédente
            $previousAverage = $this->calculateStudentAverageForYear($student->id, $lastEnrollment->academic_year_id);
            
            // Déterminer si l'élève passe ou redouble
            $hasPassed = $previousAverage >= 10;
            
            $studentStatus = 'nouveau';
            $previousYearResult = 'non_applicable';
            $statusComments = '';
            $suggestedClass = null;
            
            if ($hasPassed) {
                $previousYearResult = 'admis';
                $studentStatus = 'passant';
                $statusComments = "Admis avec moyenne de {$previousAverage}/20 - Passage en classe supérieure";
                
                // Suggérer la classe suivante
                $nextLevel = Level::where('cycle', $lastEnrollment->schoolClass->level->cycle)
                    ->where('order', $lastEnrollment->schoolClass->level->order + 1)
                    ->first();
                
                if ($nextLevel) {
                    $nextClass = SchoolClass::where('level_id', $nextLevel->id)->first();
                    if ($nextClass) {
                        $suggestedClass = [
                            'class_id' => $nextClass->id,
                            'class_name' => $nextClass->name,
                            'level_id' => $nextLevel->id,
                            'level_name' => $nextLevel->name,
                            'cycle' => $nextLevel->cycle
                        ];
                    }
                }
            } else {
                $previousYearResult = 'redouble';
                $studentStatus = 'redoublant';
                $statusComments = "Moyenne insuffisante ({$previousAverage}/20) - Redoublement";
                
                // Suggérer la même classe
                $suggestedClass = [
                    'class_id' => $lastEnrollment->schoolClass->id,
                    'class_name' => $lastEnrollment->schoolClass->name,
                    'level_id' => $lastEnrollment->schoolClass->level_id,
                    'level_name' => $lastEnrollment->schoolClass->level->name,
                    'cycle' => $lastEnrollment->schoolClass->level->cycle
                ];
            }
            
            return response()->json([
                'success' => true,
                'student_status' => $studentStatus,
                'previous_class_id' => $lastEnrollment->schoolClass->id,
                'previous_academic_year_id' => $lastEnrollment->academic_year_id,
                'previous_year_result' => $previousYearResult,
                'previous_year_average' => $previousAverage,
                'status_comments' => $statusComments,
                'student_info' => [
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'student_id' => $student->student_id,
                    'date_of_birth' => $student->date_of_birth->format('Y-m-d'),
                    'gender' => $student->gender,
                    'phone' => $student->emergency_contact ?? '',
                    'email' => '',
                    'address' => $student->address ?? '',
                    'current_status' => $student->current_status,
                    'total_enrollments' => $student->total_enrollments,
                    'total_redoublements' => $student->total_redoublements
                ],
                'previous_class' => $lastEnrollment->schoolClass->name ?? 'N/A',
                'suggested_class' => $suggestedClass
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la recherche de l\'élève pour réinscription: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la recherche de l\'élève',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 