<?php

namespace App\Http\Controllers;

use App\Models\ParentAccount;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\OnlinePayment;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ParentPortalController extends Controller
{
    /**
     * Afficher la page de connexion du portail parent
     */
    public function showLogin()
    {
        return view('parent-portal.login');
    }

    /**
     * Traiter la connexion du parent
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Vérifier que l'utilisateur existe et a le rôle 'parent'
        $user = User::where('email', $request->email)
            ->where('role', 'parent')
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Aucun compte parent trouvé avec cet email.'])->withInput();
        }

        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Mot de passe incorrect.'])->withInput();
        }

        // Trouver le parent associé à cet utilisateur
        $parent = ParentModel::where('user_id', $user->id)->first();
        
        if (!$parent) {
            return back()->withErrors(['email' => 'Aucun profil parent trouvé pour cet utilisateur.'])->withInput();
        }

        // Authentifier l'utilisateur avec Laravel
        Auth::login($user);

        // Créer une session pour le parent
        session(['parent_id' => $parent->id]);
        session(['user_id' => $user->id]);

        // Mettre à jour la dernière connexion
        $user->update(['last_login_at' => now()]);

        return redirect()->route('parent-portal.dashboard');
    }

    /**
     * Déconnexion du parent
     */
    public function logout()
    {
        Auth::logout();
        session()->forget(['parent_id', 'user_id']);
        session()->invalidate();
        session()->regenerateToken();
        
        return redirect()->route('parent-portal.login');
    }

    /**
     * Dashboard principal du parent
     */
    public function dashboard()
    {
        $user = Auth::user();
        $parent = $this->getCurrentParent();
        $children = $parent->students;
        $annee = \App\Models\AcademicYear::where('is_current', true)->first();

        $stats = [
            'total_children' => $children->count(),
            'active_enrollments' => \App\Models\Enrollment::whereIn('student_id', $children->pluck('id'))
                ->where('status', 'active')
                ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
                ->count(),
            'total_payments' => OnlinePayment::where('parent_id', $parent->id)->count(),
            'completed_payments' => OnlinePayment::where('parent_id', $parent->id)
                ->where('status', 'completed')->count(),
        ];

        /*
         * Une fiche par enfant : sa classe, sa moyenne, ses absences et ou en
         * est sa scolarite. L'ancien tableau de bord n'affichait que des noms,
         * et il fallait ouvrir chaque dossier pour savoir quoi que ce soit.
         */
        $inscriptions = \App\Models\Enrollment::with('schoolClass.level')
            ->whereIn('student_id', $children->pluck('id'))
            ->where('status', 'active')
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
            ->get()
            ->keyBy('student_id');

        $moyennes = \App\Models\StudentGrade::whereIn('student_id', $children->pluck('id'))
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
            ->where('max_score', '>', 0)
            ->selectRaw('student_id, count(*) as notes, avg(score / max_score * 20) as moyenne')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $absences = \App\Models\Attendance::whereIn('student_id', $children->pluck('id'))
            ->where('status', 'absent')
            ->when($annee, fn ($q) => $q->whereBetween('attendance_date', [$annee->start_date, $annee->end_date]))
            ->selectRaw('student_id, count(*) as absences')
            ->groupBy('student_id')
            ->pluck('absences', 'student_id');

        $absencesDuMois = \App\Models\Attendance::whereIn('student_id', $children->pluck('id'))
            ->where('status', 'absent')
            ->whereMonth('attendance_date', now()->month)
            ->whereYear('attendance_date', now()->year)
            ->count();

        $fiches = $children->mapWithKeys(function ($enfant) use ($inscriptions, $moyennes, $absences) {
            $inscription = $inscriptions[$enfant->id] ?? null;
            $note = $moyennes[$enfant->id] ?? null;

            $du = (float) ($inscription->total_fees ?? 0);
            $paye = (float) ($inscription->amount_paid ?? 0);

            return [$enfant->id => [
                'classe' => $inscription?->schoolClass?->name,
                'niveau' => $inscription?->schoolClass?->level?->name,
                'moyenne' => $note ? round((float) $note->moyenne, 2) : null,
                'notes' => (int) ($note->notes ?? 0),
                'absences' => (int) ($absences[$enfant->id] ?? 0),
                'du' => $du,
                'paye' => $paye,
                'reste' => max(0, $du - $paye),
            ]];
        });

        $bilan = [
            'du' => $fiches->sum('du'),
            'paye' => $fiches->sum('paye'),
            'reste' => $fiches->sum('reste'),
            'absences' => $absencesDuMois,
        ];

        $recentPayments = \App\Models\Payment::whereIn('student_id', $children->pluck('id'))
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        // Ce qui vient d'arriver : les notes et les absences les plus recentes,
        // tous enfants confondus. C'est ce qu'un parent ouvre l'application pour
        // voir, et cela ne figurait nulle part.
        $dernieresNotes = \App\Models\StudentGrade::with('subject:id,name')
            ->whereIn('student_id', $children->pluck('id'))
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
            ->where('max_score', '>', 0)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $dernieresAbsences = \App\Models\Attendance::whereIn('student_id', $children->pluck('id'))
            ->whereIn('status', ['absent', 'late', 'excused'])
            ->when($annee, fn ($q) => $q->whereBetween('attendance_date', [$annee->start_date, $annee->end_date]))
            ->orderByDesc('attendance_date')
            ->limit(8)
            ->get(['student_id', 'attendance_date', 'status', 'reason']);

        $moyennes_valides = $fiches->pluck('moyenne')->filter(fn ($m) => $m !== null);
        $bilan['moyenne'] = $moyennes_valides->isNotEmpty()
            ? round($moyennes_valides->avg(), 2)
            : null;

        return view('parent-portal.dashboard', compact(
            'user', 'parent', 'children', 'stats', 'recentPayments',
            'fiches', 'bilan', 'annee', 'dernieresNotes', 'dernieresAbsences'
        ));
    }

    /**
     * La liste de mes enfants : une carte par dossier.
     */
    public function children()
    {
        $donnees = $this->dashboard()->getData();

        return view('parent-portal.children', $donnees);
    }

    /**
     * Afficher les informations d'un enfant
     */
    public function childDetails($studentId)
    {
        $parent = $this->getCurrentParent();

        $student = $parent->students()->where('students.id', $studentId)->first();

        if (! $student) {
            abort(404, 'Élève non trouvé.');
        }

        $annee = \App\Models\AcademicYear::where('is_current', true)->first();

        $currentEnrollment = $student->enrollments()
            ->with(['schoolClass.level', 'academicYear'])
            ->where('status', 'active')
            ->first();

        /*
         * Les notes : toutes celles de l'année, et non les dix dernières.
         * Un parent a droit aux données entières de son enfant ; une moyenne
         * calculée sur un échantillon serait fausse.
         */
        $notes = \App\Models\StudentGrade::with(['subject:id,name', 'teacher:id,first_name,last_name'])
            ->where('student_id', $student->id)
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
            ->where('max_score', '>', 0)
            ->orderByDesc('created_at')
            ->get();

        $parMatiere = $notes
            ->groupBy('subject_id')
            ->map(fn ($lot) => [
                'matiere' => $lot->first()->subject->name ?? 'Matière supprimée',
                'notes' => $lot->count(),
                'moyenne' => round($lot->avg(fn ($n) => $n->score / $n->max_score * 20), 2),
            ])
            ->sortByDesc('moyenne')
            ->values();

        $moyenneGenerale = $notes->isNotEmpty()
            ? round($notes->avg(fn ($n) => $n->score / $n->max_score * 20), 2)
            : null;

        // --- Assiduité, sur toute l'année ---------------------------------
        $pointages = $student->attendances()
            ->when($annee, fn ($q) => $q->whereBetween('attendance_date', [$annee->start_date, $annee->end_date]))
            ->orderByDesc('attendance_date')
            ->get(['attendance_date', 'status', 'reason', 'justified']);

        $assiduite = [
            'total' => $pointages->count(),
            'present' => $pointages->where('status', 'present')->count(),
            'absent' => $pointages->where('status', 'absent')->count(),
            'late' => $pointages->where('status', 'late')->count(),
            'excused' => $pointages->where('status', 'excused')->count(),
        ];

        $assiduite['taux'] = $assiduite['total'] > 0
            ? round($assiduite['present'] / $assiduite['total'] * 100)
            : null;

        $absences = $pointages->whereIn('status', ['absent', 'late', 'excused'])->take(30)->values();

        // --- L'emploi du temps de sa classe -------------------------------
        $creneaux = $currentEnrollment
            ? \App\Models\Schedule::with(['subject:id,name', 'teacher:id,first_name,last_name'])
                ->where('class_id', $currentEnrollment->class_id)
                ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
            : collect();

        // --- Ce qui a été payé pour lui -----------------------------------
        $paiements = \App\Models\Payment::where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();

        $enLigne = OnlinePayment::where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();

        return view('parent-portal.child-details', [
            'parent' => $parent,
            'student' => $student,
            'annee' => $annee,
            'currentEnrollment' => $currentEnrollment,
            'notes' => $notes,
            'parMatiere' => $parMatiere,
            'moyenneGenerale' => $moyenneGenerale,
            'assiduite' => $assiduite,
            'absences' => $absences,
            'creneaux' => $creneaux,
            'paiements' => $paiements,
            'enLigne' => $enLigne,
            'onglet' => request('onglet', 'scolarite'),
        ]);
    }

    /**
     * Afficher les notes d'un enfant
     */
    public function childGrades($studentId)
    {
        // Les notes et l'assiduité ne sont pas des pages séparées : ce sont
        // deux onglets de la fiche de l'enfant, qui les tient toutes.
        return redirect()->route('parent-portal.child-details', [$studentId, 'onglet' => 'notes']);
    }

    /**
     * Afficher les présences d'un enfant
     */
    public function childAttendance($studentId)
    {
        return redirect()->route('parent-portal.child-details', [$studentId, 'onglet' => 'assiduite']);
    }

    /**
     * Afficher l'historique des paiements
     */
    public function paymentHistory()
    {
        $parent = $this->getCurrentParent();

        $enfants = $parent->students;

        /*
         * L'historique ne portait que sur les paiements en ligne : les
         * versements encaisses au guichet, qui sont l'essentiel, n'y
         * figuraient pas. Il porte desormais sur tout ce qui a ete paye pour
         * ses enfants, quel qu'en soit le canal.
         */
        $payments = \App\Models\Payment::whereIn('student_id', $enfants->pluck('id'))
            ->with('student:id,first_name,last_name,student_id')
            ->orderByDesc('created_at')
            ->paginate(15);

        $bilan = [
            'total' => (float) \App\Models\Payment::whereIn('student_id', $enfants->pluck('id'))
                ->where('status', 'completed')->sum('amount'),
            'nombre' => \App\Models\Payment::whereIn('student_id', $enfants->pluck('id'))->count(),
            'en_ligne' => OnlinePayment::where('parent_id', $parent->id)->count(),
        ];

        return view('parent-portal.payment-history', compact('parent', 'payments', 'enfants', 'bilan'));
    }

    /**
     * Afficher le profil du parent
     */
    public function profile()
    {
        $parent = $this->getCurrentParent();

        $user = Auth::user();
        return view('parent-portal.profile', compact('user', 'parent'));
    }

    /**
     * Mettre à jour le profil du parent
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $parent = $this->getCurrentParent();

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,' . $user->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Mettre à jour l'utilisateur
        $user->update([
            'email' => $request->email,
            'name' => $request->first_name . ' ' . $request->last_name
        ]);

        // Mettre à jour le parent
        $parent->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'address' => $request->address
        ]);

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.'])->withInput();
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return back()->with('success', 'Mot de passe modifié avec succès.');
    }

    /**
     * Afficher la page d'inscription en ligne
     */
    public function showOnlineEnrollment()
    {
        // L'inscription en ligne s'ouvre et se ferme depuis les parametres de
        // la plateforme : hors periode, le formulaire ne doit pas etre servi.
        abort_unless(\App\Support\ParametresPlateforme::actif('inscriptions_en_ligne'), 403,
            'Les inscriptions en ligne sont actuellement fermees.');

        $levels = \App\Models\Level::active()->orderBy('order')->get();
        $classes = \App\Models\SchoolClass::with('level')->active()->get();
        $academicYears = \App\Models\AcademicYear::where('status', 'active')->get();
        $paymentGateways = \App\Models\PaymentGateway::getActiveGateways();

        return view('parent-portal.online-enrollment', compact('levels', 'classes', 'academicYears', 'paymentGateways'));
    }

    /**
     * Traiter l'inscription en ligne
     */
    public function processOnlineEnrollment(Request $request)
    {
        $rules = [
            'enrollment_type' => 'required|in:new,renewal',
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'payment_method' => 'required|in:moov_money,airtel_money',
            'payer_phone' => 'required|string|max:20'
        ];

        // Règles spécifiques selon le type d'inscription
        if ($request->enrollment_type === 'new') {
            $rules = array_merge($rules, [
                'student_first_name' => 'required|string|max:255',
                'student_last_name' => 'required|string|max:255',
                'student_date_of_birth' => 'required|date',
                'student_gender' => 'required|in:male,female',
                'parent_first_name' => 'required|string|max:255',
                'parent_last_name' => 'required|string|max:255',
                'parent_phone' => 'required|string|max:20',
                'parent_email' => 'required|email'
            ]);
        } else {
            $rules = array_merge($rules, [
                'existing_student_id' => 'required|exists:students,id',
                'parent_first_name' => 'required|string|max:255',
                'parent_last_name' => 'required|string|max:255',
                'parent_phone' => 'required|string|max:20',
                'parent_email' => 'required|email'
            ]);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $isRenewal = $request->enrollment_type === 'renewal';
        $amount = $isRenewal ? 30000 : 50000; // Réinscription moins chère
        
        // Créer l'inscription en attente
        $enrollmentData = [
            'class_id' => $request->class_id,
            'academic_year_id' => $request->academic_year_id,
            'enrollment_status' => 'pending_payment',
            'parent_first_name' => $request->parent_first_name,
            'parent_last_name' => $request->parent_last_name,
            'parent_phone' => $request->parent_phone,
            'parent_email' => $request->parent_email,
            'total_fees' => $amount,
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending'
        ];

        if ($isRenewal) {
            // Réinscription
            $student = Student::find($request->existing_student_id);
            $enrollmentData['student_id'] = $student->id;
            $enrollmentData['is_new_enrollment'] = false;
            $enrollmentData['applicant_first_name'] = $student->first_name;
            $enrollmentData['applicant_last_name'] = $student->last_name;
            $enrollmentData['applicant_date_of_birth'] = $student->date_of_birth;
            $enrollmentData['applicant_gender'] = $student->gender;
        } else {
            // Nouvelle inscription
            $enrollmentData['is_new_enrollment'] = true;
            $enrollmentData['applicant_first_name'] = $request->student_first_name;
            $enrollmentData['applicant_last_name'] = $request->student_last_name;
            $enrollmentData['applicant_date_of_birth'] = $request->student_date_of_birth;
            $enrollmentData['applicant_gender'] = $request->student_gender;
        }

        $enrollment = \App\Models\Enrollment::create($enrollmentData);

        // Créer le paiement en ligne
        $paymentData = [
            'transaction_id' => OnlinePayment::generateTransactionId(),
            'enrollment_id' => $enrollment->id,
            'amount' => $amount,
            'payment_type' => $isRenewal ? 're_enrollment' : 'enrollment',
            'payment_method' => $request->payment_method,
            'payer_name' => $request->parent_first_name . ' ' . $request->parent_last_name,
            'payer_phone' => $request->payer_phone,
            'payer_email' => $request->parent_email,
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ];

        if ($isRenewal) {
            $paymentData['student_id'] = $student->id;
        }

        $payment = OnlinePayment::create($paymentData);

        return redirect()->route('parent-portal.payment', $payment->transaction_id);
    }

    /**
     * Afficher la page de paiement
     */
    public function showPayment($transactionId)
    {
        $payment = OnlinePayment::where('transaction_id', $transactionId)->firstOrFail();
        $gateway = PaymentGateway::byCode($payment->payment_method)->first();

        return view('parent-portal.payment', compact('payment', 'gateway'));
    }

    /**
     * Traiter le paiement
     */
    public function processPayment(Request $request, $transactionId)
    {
        $payment = OnlinePayment::where('transaction_id', $transactionId)->firstOrFail();
        
        // Simuler le traitement du paiement (en production, intégrer avec les vraies APIs)
        $payment->update([
            'status' => 'processing',
            'gateway_response' => json_encode($request->all())
        ]);

        // Simuler une réponse de succès après 2 secondes
        sleep(2);
        
        $payment->markAsCompleted('GATEWAY_' . strtoupper(Str::random(8)));

        // Mettre à jour l'inscription
        if ($payment->enrollment) {
            $payment->enrollment->update([
                'enrollment_status' => 'completed',
                'payment_status' => 'completed',
                'amount_paid' => $payment->amount,
                'receipt_number' => 'RCP' . date('Ymd') . strtoupper(Str::random(6))
            ]);
        }

        return redirect()->route('parent-portal.payment-success', $transactionId);
    }

    /**
     * Page de succès du paiement
     */
    public function paymentSuccess($transactionId)
    {
        $payment = OnlinePayment::where('transaction_id', $transactionId)->firstOrFail();
        
        return view('parent-portal.payment-success', compact('payment'));
    }

    /**
     * Obtenir le parent actuel
     */
    /* ==================================================================
       Régler la scolarité par téléphone
       ================================================================== */

    /**
     * Où payer, comment, et ce qui reste dû.
     *
     * La plateforme n'encaisse rien : elle affiche le code marchand de
     * l'établissement, la marche à suivre, puis recueille la déclaration du
     * parent. Le secrétariat vérifie le SMS de l'opérateur avant de valider —
     * porter « réglé » sur un reçu sans que personne ait constaté le versement
     * reviendrait à écrire un faux.
     */
    public function paiement(Request $request)
    {
        $parent = $this->getCurrentParent();

        $reglages = \App\Models\SchoolSettings::getSettings();
        $operateurs = \App\Support\MobileMoney::disponibles($reglages);

        // Ce que chaque enfant doit encore, inscription en cours.
        $dossiers = $parent->students->map(function ($enfant) {
            $inscription = $enfant->enrollments()
                ->with(['schoolClass:id,name', 'academicYear:id,name'])
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if (! $inscription) {
                return null;
            }

            $du = (float) $inscription->total_fees;
            $verse = (float) $inscription->amount_paid;

            return [
                'eleve' => $enfant,
                'inscription' => $inscription,
                'du' => $du,
                'verse' => $verse,
                'reste' => max(0, $du - $verse),
                // Ce qui a été déclaré et attend encore la vérification : le
                // parent doit le voir, sinon il paie deux fois.
                'en_attente' => (float) \App\Models\Payment::where('student_id', $enfant->id)
                    ->whereIn('status', ['pending', 'processing'])
                    ->sum('amount'),
            ];
        })->filter()
            // Ceux qui doivent encore d'abord : c'est pour eux qu'on vient ici.
            ->sortByDesc('reste')
            ->values();

        return view('parent-portal.paiement', [
            'parent' => $parent,
            'reglages' => $reglages,
            'operateurs' => $operateurs,
            'dossiers' => $dossiers,
            'choisi' => $request->integer('enfant') ?: ($dossiers->first()['eleve']->id ?? null),
        ]);
    }

    /**
     * Enregistrer la déclaration de versement.
     *
     * Le paiement naît « en attente » : c'est une déclaration, pas un
     * encaissement. Il porte l'identifiant de transaction du SMS, seul élément
     * que le secrétariat puisse confronter au relevé de l'opérateur.
     */
    public function declarerLePaiement(Request $request)
    {
        $parent = $this->getCurrentParent();

        $reglages = \App\Models\SchoolSettings::getSettings();
        $operateurs = \App\Support\MobileMoney::disponibles($reglages);

        if ($operateurs === []) {
            return back()->with('error', 'Le paiement par téléphone n’est pas ouvert dans cet établissement.');
        }

        $donnees = $request->validate([
            'student_id' => 'required|integer',
            'operateur' => 'required|in:'.implode(',', array_keys($operateurs)),
            'montant' => 'required|numeric|min:100',
            'reference' => 'required|string|max:100',
            'telephone' => 'nullable|string|max:30',
        ], [], [
            'student_id' => 'enfant',
            'operateur' => 'opérateur',
            'reference' => 'identifiant de la transaction',
        ]);

        // L'enfant est-il bien le sien ? L'identifiant vient d'un formulaire,
        // et un formulaire se modifie.
        $enfant = $parent->students()->where('students.id', $donnees['student_id'])->first();

        if (! $enfant) {
            abort(403, 'Cet élève ne fait pas partie de vos enfants.');
        }

        // Deux fois le même identifiant de transaction, c'est la même
        // opération déclarée deux fois.
        $dejaDeclare = \App\Models\Payment::where('gateway_transaction_id', $donnees['reference'])->first();

        if ($dejaDeclare) {
            return back()->withInput()->withErrors([
                'reference' => 'Cette transaction a déjà été déclarée le '
                    .$dejaDeclare->created_at->format('d/m/Y à H:i').'.',
            ]);
        }

        $inscription = $enfant->enrollments()->where('status', 'active')->latest('id')->first();

        $paiement = \App\Models\Payment::create([
            'transaction_id' => \App\Models\Payment::generateTransactionId(),
            'enrollment_id' => $inscription?->id,
            'parent_id' => $parent->id,
            'student_id' => $enfant->id,
            'amount' => $donnees['montant'],
            'currency' => 'XAF',
            'payment_type' => 'tuition',
            'payment_method' => $donnees['operateur'],
            'status' => 'pending',
            'payer_name' => trim($parent->first_name.' '.$parent->last_name),
            'payer_phone' => $donnees['telephone'] ?: $parent->phone,
            'payer_email' => $parent->email,
            'gateway_transaction_id' => $donnees['reference'],
            'notes' => 'Versement déclaré depuis le portail parent, à vérifier auprès de '
                .\App\Support\MobileMoney::libelle($donnees['operateur']).'.',
            'metadata' => [
                'canal' => 'portail_parent',
                'declare_le' => now()->toDateTimeString(),
                'code_marchand' => $operateurs[$donnees['operateur']]['code'],
            ],
            'ip_address' => $request->ip(),
            'school_id' => $enfant->school_id,
        ]);

        return redirect()->route('payments.receipt', $paiement)
            ->with('success', 'Votre versement a été déclaré. Le secrétariat le validera après vérification auprès de l’opérateur.');
    }

    private function getCurrentParent()
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'parent') {
            abort(401, 'Non authentifié ou accès non autorisé.');
        }

        $parent = ParentModel::where('user_id', $user->id)->first();
        
        if (!$parent) {
            abort(404, 'Profil parent non trouvé.');
        }

        return $parent;
    }
}
