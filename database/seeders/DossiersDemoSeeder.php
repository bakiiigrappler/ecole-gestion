<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\Level;
use App\Models\Schedule;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Complète les dossiers scolaires que le peuplement laisse vides.
 *
 * Un compte de démonstration dont les écrans sont vides ne démontre rien, et
 * c'est exactement ce que donnaient les comptes élèves : « Aucune note n'a
 * encore été saisie pour vous », moyenne générale à blanc, emploi du temps
 * sans un seul cours. Trois causes, cumulées :
 *
 * - les notes ne couvraient que deux cents inscriptions, prises par `limit()`
 *   sur un millier — rien ne garantissait que les élèves titulaires d'un
 *   compte en fassent partie, et sur base neuve ils n'en faisaient pas partie ;
 * - les emplois du temps manquaient à un tiers des classes du secondaire ;
 * - le second établissement n'a ni notes, ni emplois du temps, ni appel :
 *   son propre seeder ne crée que classes, élèves et enseignants. Or ses
 *   terminales sont les seules à passer le baccalauréat.
 *
 * Ce seeder passe donc en dernier, hors établissement, et ne comble que les
 * trous : une classe qui a déjà son emploi du temps n'y est pas touchée, un
 * élève qui a déjà des notes n'en reçoit pas d'autres. Il est donc rejouable
 * sur une base existante, ce qui est tout l'intérêt quand on s'aperçoit du
 * manque après coup.
 */
class DossiersDemoSeeder extends Seeder
{
    /** Les trois trimestres, tels que les bulletins les nomment. */
    private const TRIMESTRES = ['1er trimestre', '2ème trimestre', '3ème trimestre'];

    /** Combien de jours d'appel poser quand une classe n'en a aucun. */
    private const JOURS_D_APPEL = 12;

    /** Les motifs d'absence, pris au peuplement des présences. */
    private const MOTIFS = [
        'Maladie', 'Rendez-vous médical', 'Raison familiale',
        'Transport', 'Absence non justifiée',
    ];

    public function run(): void
    {
        /*
         * L'année scolaire courante se lit par établissement, et non une fois
         * pour toutes.
         *
         * Chaque école tient la sienne : la première a l'année 1, le second
         * établissement l'année 9, toutes deux marquées « courante ». Prendre la
         * première venue faisait travailler ce seeder sur une seule école : les
         * inscriptions de l'autre portaient un identifiant d'année qui ne
         * figurait pas dans la requête, et ses élèves repartaient sans notes.
         * C'est le piège habituel de cette base, et il ne se voit qu'au second
         * établissement.
         */
        $this->anneesParEcole();

        if ($this->annees === []) {
            $this->command?->warn('Aucune année scolaire : dossiers ignorés.');

            return;
        }

        $creneaux = $this->completerLesEmploisDuTemps();
        $notes = $this->completerLesNotes();
        $appels = $this->completerLAppel();
        [$frais, $paiements] = $this->completerLaCaisse();
        $marques = $this->marquerQuelquesAbsences();

        $this->command?->info(sprintf(
            'Dossiers complétés : %d créneau(x), %d note(s), %d jour(s) d’appel, %d frais, %d paiement(s), %d dossier(s) d’absence.',
            $creneaux,
            $notes,
            $appels,
            $frais,
            $paiements,
            $marques
        ));
    }

    /**
     * Un emploi du temps aux classes qui n'en ont pas.
     *
     * Cinq jours, quatre heures de cours par jour, prises dans les matières du
     * cycle. Ce n'est pas une grille d'établissement — elle se construit
     * ailleurs — mais c'en est assez pour que « Mon emploi du temps » montre
     * une semaine plutôt qu'un écran vide.
     */
    private function completerLesEmploisDuTemps(): int
    {
        $horaires = [['07:30', '09:30'], ['09:45', '11:45'], ['13:00', '15:00'], ['15:15', '17:15']];
        $lignes = [];

        foreach ($this->classesDuSecondaire() as $classe) {
            $dejaServie = Schedule::withoutGlobalScopes()
                ->where('class_id', $classe->id)
                ->exists();

            if ($dejaServie) {
                continue;
            }

            $matieres = $this->matieresDuCycle($classe);
            $enseignants = $this->enseignantsDe($classe->school_id);
            $annee = $this->anneeDe($classe->school_id);

            if ($matieres->isEmpty() || $enseignants->isEmpty() || ! $annee) {
                continue;
            }

            $rang = 0;

            // Lundi à vendredi : les jours sont numérotés depuis 1, comme
            // partout ailleurs dans l'application.
            foreach (range(1, 5) as $jour) {
                foreach ($horaires as [$debut, $fin]) {
                    $matiere = $matieres[$rang % $matieres->count()];
                    $enseignant = $enseignants[$rang % $enseignants->count()];
                    $rang++;

                    $lignes[] = [
                        'class_id' => $classe->id,
                        'school_id' => $classe->school_id,
                        'academic_year_id' => $annee,
                        'day_of_week' => $jour,
                        'start_time' => $debut.':00',
                        'end_time' => $fin.':00',
                        'subject_id' => $matiere->id,
                        'teacher_id' => $enseignant->id,
                        'type' => 'course',
                        'title' => null,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($lignes, 500) as $lot) {
            DB::table('schedules')->insert($lot);
        }

        return count($lignes);
    }

    /**
     * Des notes aux élèves du secondaire qui n'en ont aucune.
     *
     * Huit matières, trois trimestres, et un niveau propre à chaque élève
     * autour duquel les notes gravitent : sans cela toutes les moyennes se
     * vaudraient, et ni un bulletin ni un dossier d'orientation n'auraient
     * quoi que ce soit à trancher.
     */
    private function completerLesNotes(): int
    {
        // Qui a deja une note, toutes annees confondues : on complete, on ne
        // double pas.
        $dejaNotes = DB::table('student_grades')->distinct()->pluck('student_id')->all();

        $inscriptions = Enrollment::withoutGlobalScopes()
            ->with('schoolClass.level')
            ->whereIn('academic_year_id', array_values($this->annees))
            ->where('status', 'active')
            ->whereHas('schoolClass.level', fn ($q) => $q->whereIn('cycle', ['college', 'lycee']))
            ->when($dejaNotes !== [], fn ($q) => $q->whereNotIn('student_id', $dejaNotes))
            ->get();

        $notes = [];
        $maintenant = now();

        foreach ($inscriptions as $inscription) {
            $classe = $inscription->schoolClass;

            if (! $classe) {
                continue;
            }

            $matieres = $this->matieresDuCycle($classe);
            $enseignants = $this->enseignantsDe($classe->school_id);

            if ($matieres->isEmpty() || $enseignants->isEmpty()) {
                continue;
            }

            $niveau = mt_rand(80, 150) / 10;

            foreach ($matieres as $rang => $matiere) {
                foreach (self::TRIMESTRES as $trimestre) {
                    $notes[] = [
                        'student_id' => $inscription->student_id,
                        'subject_id' => $matiere->id,
                        'class_id' => $classe->id,
                        'school_id' => $classe->school_id,
                        'academic_year_id' => $inscription->academic_year_id,
                        'teacher_id' => $enseignants[$rang % $enseignants->count()]->id,
                        'term' => $trimestre,
                        'score' => max(0, min(20, round($niveau + mt_rand(-30, 30) / 10, 2))),
                        'max_score' => 20,
                        'created_at' => $maintenant,
                        'updated_at' => $maintenant,
                    ];
                }
            }
        }

        foreach (array_chunk($notes, 500) as $lot) {
            DB::table('student_grades')->insert($lot);
        }

        return count($notes);
    }

    /**
     * L'appel, pour les classes où il n'a jamais été fait.
     *
     * La colonne ABSENCES du bulletin vient de là, et « Mes absences » aussi.
     * Un élève sur douze traîne un absentéisme chronique : sans eux, tous les
     * taux se ressemblent et l'écran n'apprend rien.
     */
    private function completerLAppel(): int
    {
        $jours = $this->joursOuvres(self::JOURS_D_APPEL);
        $lignes = [];
        $classesServies = 0;

        foreach ($this->classesDuSecondaire() as $classe) {
            $dejaAppelee = Attendance::withoutGlobalScopes()
                ->where('class_id', $classe->id)
                ->exists();

            if ($dejaAppelee) {
                continue;
            }

            $eleves = Enrollment::withoutGlobalScopes()
                ->where('class_id', $classe->id)
                ->whereIn('academic_year_id', array_values($this->annees))
                ->where('status', 'active')
                ->pluck('student_id');

            if ($eleves->isEmpty()) {
                continue;
            }

            $classesServies++;
            $fragiles = $eleves->random(max(1, (int) floor($eleves->count() / 12)))->all();

            foreach ($jours as $jour) {
                foreach ($eleves as $eleve) {
                    $statut = $this->tirerLeStatut(in_array($eleve, $fragiles, true));

                    $lignes[] = [
                        'student_id' => $eleve,
                        'class_id' => $classe->id,
                        'school_id' => $classe->school_id,
                        'attendance_date' => $jour,
                        'time_slot' => 'journee',
                        'status' => $statut,
                        'arrival_time' => $statut === 'late' ? sprintf('08:%02d:00', random_int(5, 45)) : null,
                        'reason' => in_array($statut, ['absent', 'excused'], true)
                            ? self::MOTIFS[array_rand(self::MOTIFS)]
                            : null,
                        'justified' => $statut === 'excused',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($lignes, 1000) as $lot) {
            DB::table('attendances')->insert($lot);
        }

        return $classesServies;
    }

    /**
     * Une grille de frais et des encaissements, pour les écoles qui n'en ont
     * aucun.
     *
     * Le second établissement n'avait ni frais ni paiement : sa caisse, ses
     * relances et ses reçus étaient vides, et le portail de ses parents
     * n'avait rien à régler. Une école sans frais n'existe pas.
     *
     * Les situations sont volontairement inégales — soldé, partiel, impayé —
     * parce que c'est cette inégalité que les écrans de recouvrement servent à
     * lire. Une école où tout le monde a payé ne demande aucun suivi.
     *
     * @return array{0: int, 1: int}
     */
    private function completerLaCaisse(): array
    {
        $frais = [];
        $paiements = [];
        $maintenant = now();

        foreach (School::withoutGlobalScopes()->get(['id', 'name']) as $ecole) {
            $annee = $this->anneeDe($ecole->id);

            if (! $annee || Fee::withoutGlobalScopes()->where('school_id', $ecole->id)->exists()) {
                continue;
            }

            $niveaux = Level::withoutGlobalScopes()
                ->where('school_id', $ecole->id)
                ->get(['id', 'name', 'cycle']);

            if ($niveaux->isEmpty()) {
                continue;
            }

            foreach ($niveaux as $niveau) {
                // Le tarif suit le cycle : un lycéen coûte plus qu'un élève de
                // maternelle, et la grille doit le dire.
                $scolarite = match ($niveau->cycle) {
                    'lycee' => 450000,
                    'college' => 350000,
                    'primaire' => 250000,
                    default => 180000,
                };

                $frais[] = $this->ligneDeFrais($ecole->id, $annee, $niveau->id,
                    'Frais de scolarité '.$niveau->name, $scolarite, 'tuition', 'yearly', $maintenant);

                $frais[] = $this->ligneDeFrais($ecole->id, $annee, $niveau->id,
                    'Frais d\'inscription '.$niveau->name, 50000, 'registration', 'one_time', $maintenant);
            }
        }

        foreach (array_chunk($frais, 200) as $lot) {
            DB::table('fees')->insert($lot);
        }

        /*
         * Les encaissements se posent independamment de la grille qu'on vient
         * d'ecrire.
         *
         * Court-circuiter ici quand aucun frais n'a ete cree laissait sans
         * paiement une ecole dont la grille existait deja mais dont la caisse
         * etait vide : c'est exactement le cas qu'on voulait combler.
         */
        $grille = DB::table('fees')
            ->whereNotNull('level_id')
            ->get(['school_id', 'level_id', 'amount']);

        if ($grille->isEmpty()) {
            return [count($frais), 0];
        }

        $rang = 0;

        foreach ($this->inscriptionsSansPaiement() as $inscription) {
            $classe = $inscription->schoolClass;

            if (! $classe) {
                continue;
            }

            $du = (float) $grille
                ->where('school_id', $classe->school_id)
                ->where('level_id', $classe->level_id)
                ->sum('amount');

            if ($du <= 0) {
                continue;
            }

            // Soldé, partiel, impayé : deux tiers, un cinquième, le reste.
            $part = match ($rang % 6) {
                0, 1, 2, 3 => 1.0,
                4 => round(mt_rand(30, 70) / 100, 2),
                default => 0.0,
            };

            $encaisse = round($du * $part, 2);
            $reste = round($du - $encaisse, 2);
            $rang++;

            $inscription->forceFill([
                'total_fees' => $du,
                'amount_paid' => $encaisse,
                'balance_due' => $reste,
                'payment_status' => $encaisse <= 0 ? 'pending' : ($reste > 0 ? 'partial' : 'completed'),
            ])->save();

            if ($encaisse <= 0) {
                continue;
            }

            $paye = $maintenant->copy()->subDays(mt_rand(5, 200));

            $paiements[] = [
                'transaction_id' => sprintf('TRX-%d-%06d', $inscription->academic_year_id, $inscription->id),
                'enrollment_id' => $inscription->id,
                'student_id' => $inscription->student_id,
                'amount' => $encaisse,
                'currency' => 'XAF',
                'payment_type' => 'enrollment',
                // Le vocabulaire impose par la contrainte de la colonne : il
                // nomme les operateurs, « mobile_money » n'en fait pas partie.
                'payment_method' => ['cash', 'airtel_money', 'moov_money', 'bank_transfer'][$rang % 4],
                'status' => 'completed',
                'payer_name' => 'Responsable légal',
                'payer_phone' => '0'.mt_rand(60000000, 77999999),
                'paid_at' => $paye,
                'receipt_number' => sprintf('REC-%s-%06d', $paye->format('Y'), $inscription->id),
                'created_at' => $paye,
                'updated_at' => $paye,
                // Pose a la main : cette insertion passe par le constructeur de
                // requetes, qui ignore le trait d'etablissement.
                'school_id' => $classe->school_id,
            ];
        }

        foreach (array_chunk($paiements, 500) as $lot) {
            DB::table('payments')->insert($lot);
        }

        return [count($frais), count($paiements)];
    }

    /** Une ligne de la grille des frais. */
    private function ligneDeFrais(int $ecole, int $annee, int $niveau, string $nom, int $montant, string $type, string $frequence, $maintenant): array
    {
        return [
            'name' => $nom,
            'description' => $nom.' pour l\'année en cours',
            'amount' => $montant,
            'level_id' => $niveau,
            'class_id' => null,
            'academic_year_id' => $annee,
            'school_id' => $ecole,
            'fee_type' => $type,
            'frequency' => $frequence,
            'is_active' => true,
            'is_mandatory' => true,
            'created_at' => $maintenant,
            'updated_at' => $maintenant,
        ];
    }

    /** Les inscriptions actives qui ne portent aucun paiement. */
    private function inscriptionsSansPaiement()
    {
        return Enrollment::withoutGlobalScopes()
            ->with('schoolClass:id,level_id,school_id')
            ->whereIn('academic_year_id', array_values($this->annees))
            ->where('status', 'active')
            ->whereDoesntHave('payments')
            ->get();
    }

    /**
     * Quelques absences aux élèves qui ont un compte.
     *
     * L'appel tire 92 % de présents, ce qui est la bonne proportion pour une
     * école mais pas pour une démonstration : sur une douzaine de jours, un
     * élève donné a de bonnes chances de n'avoir rien manqué, et son écran
     * « Mes absences » reste muet — taux de présence à 100 %, trois compteurs à
     * zéro, aucun détail. On ne juge pas un suivi d'absences sur un dossier
     * vierge.
     *
     * On retourne donc trois journées de ceux qui ont un compte : une absence,
     * un retard, une absence excusée. Et seulement s'ils n'ont rien — un
     * dossier qui raconte déjà quelque chose n'est pas réécrit.
     */
    private function marquerQuelquesAbsences(): int
    {
        $avecCompte = Student::withoutGlobalScopes()
            ->whereNotNull('user_id')
            ->pluck('id');

        if ($avecCompte->isEmpty()) {
            return 0;
        }

        // Ceux qui ont deja manque quelque chose : on les laisse tels quels.
        $deja = DB::table('attendances')
            ->whereIn('student_id', $avecCompte)
            ->where('status', '!=', 'present')
            ->distinct()
            ->pluck('student_id')
            ->all();

        $aMarquer = $avecCompte->reject(fn ($id) => in_array($id, $deja, true));
        $touches = 0;

        foreach ($aMarquer as $eleve) {
            $journees = DB::table('attendances')
                ->where('student_id', $eleve)
                ->where('status', 'present')
                ->orderBy('attendance_date')
                ->pluck('id');

            if ($journees->isEmpty()) {
                continue;
            }

            /*
             * Trois journees retournees sur cinq donnent un taux de presence de
             * 40 % : l'eleve de demonstration passait pour un decrocheur. On
             * n'en retourne donc trois que si le dossier compte assez de jours
             * pour que cela reste anecdotique, et une seule sinon.
             */
            $cas = $journees->count() >= 9
                ? [
                    [$journees[0], 'absent', 'Maladie', false],
                    [$journees[1], 'late', null, false],
                    [$journees[2], 'excused', 'Rendez-vous médical', true],
                ]
                : [[$journees[0], 'absent', 'Maladie', false]];

            foreach ($cas as [$id, $statut, $motif, $justifie]) {
                DB::table('attendances')->where('id', $id)->update([
                    'status' => $statut,
                    'reason' => $motif,
                    'justified' => $justifie,
                    'arrival_time' => $statut === 'late' ? sprintf('08:%02d:00', random_int(10, 45)) : null,
                    'updated_at' => now(),
                ]);
            }

            $touches++;
        }

        return $touches;
    }

    /* ==================================================================
       Les rouages
       ================================================================== */

    /**
     * L'année courante de chaque établissement, et un repli pour les classes
     * qui n'en déclarent aucun.
     */
    private function anneesParEcole(): void
    {
        $this->annees = AcademicYear::withoutGlobalScopes()
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get(['id', 'school_id', 'is_current'])
            ->groupBy(fn ($a) => (string) ($a->school_id ?? 'sans'))
            ->map(fn ($liste) => $liste->first()->id)
            ->all();
    }

    /** L'année courante d'un établissement. */
    private function anneeDe(?int $ecole): ?int
    {
        return $this->annees[(string) ($ecole ?? 'sans')]
            ?? ($this->annees === [] ? null : reset($this->annees));
    }

    /** Les classes du collège et du lycée, tous établissements confondus. */
    private function classesDuSecondaire()
    {
        return SchoolClass::withoutGlobalScopes()
            ->with('level:id,name,cycle')
            ->whereHas('level', fn ($q) => $q->whereIn('cycle', ['college', 'lycee']))
            ->get();
    }

    /**
     * Huit matières du cycle de cette classe.
     *
     * Les matières sont communes à la plateforme : on ne les filtre donc pas
     * par établissement, seulement par cycle.
     */
    private function matieresDuCycle(SchoolClass $classe)
    {
        /*
         * Les matieres appartiennent a un etablissement, et il faut prendre
         * celles du sien.
         *
         * Groupees par cycle seulement, elles venaient de la premiere ecole :
         * les notes du second etablissement portaient des matieres que son
         * cloisonnement lui interdit de lire, et ses bulletins affichaient
         * « Matiere supprimee » en face de chaque ligne. Son emploi du temps
         * ne montrait rien du tout, pour la meme raison.
         */
        $this->matieres ??= Subject::withoutGlobalScopes()
            ->where('is_active', true)
            ->get()
            ->groupBy(fn ($m) => ($m->school_id ?? 'sans').'|'.$m->cycle)
            ->map(fn ($liste) => $liste->take(8)->values());

        $cycle = $classe->level?->cycle;

        return $this->matieres->get($classe->school_id.'|'.$cycle)
            ?? $this->matieres->get('sans|'.$cycle, collect());
    }

    /** Les enseignants d'un établissement, à défaut ceux de la plateforme. */
    private function enseignantsDe(?int $ecole)
    {
        $this->enseignants ??= Teacher::withoutGlobalScopes()->get(['id', 'school_id', 'cycle']);

        $locaux = $this->enseignants->where('school_id', $ecole)->values();

        return $locaux->isNotEmpty() ? $locaux : $this->enseignants;
    }

    /** Les derniers jours ouvrés, du plus ancien au plus récent. */
    private function joursOuvres(int $combien): array
    {
        $jours = [];
        $jour = now()->startOfDay();

        while (count($jours) < $combien) {
            $jour = $jour->subDay();

            if (! $jour->isWeekend()) {
                $jours[] = $jour->toDateString();
            }
        }

        return array_reverse($jours);
    }

    /** Présent le plus souvent : un appel où tout le monde manque ne dit rien. */
    private function tirerLeStatut(bool $fragile): string
    {
        $tirage = mt_rand(1, 100);

        if ($fragile) {
            return match (true) {
                $tirage <= 60 => 'present',
                $tirage <= 80 => 'absent',
                $tirage <= 90 => 'late',
                default => 'excused',
            };
        }

        return match (true) {
            $tirage <= 92 => 'present',
            $tirage <= 95 => 'absent',
            $tirage <= 98 => 'late',
            default => 'excused',
        };
    }

    /** L'année courante par établissement. @var array<string, int> */
    private array $annees = [];

    /** Les matières par cycle, lues une fois. */
    private $matieres = null;

    /** Les enseignants, lus une fois. */
    private $enseignants = null;
}
