<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Remplit ce que les seeders métier laissent vide : liens parent-élève, frais
 * chiffrés, paiements encaissés et notes trimestrielles.
 *
 * Sans ces données, le tableau de bord, les statistiques et les bulletins
 * s'affichent à zéro et l'application paraît inerte.
 */
class DonneesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $annee = AcademicYear::where('is_current', true)->first();

        if (! $annee) {
            $this->command->error('Aucune année scolaire courante : lancez AcademicYearSeeder d’abord.');
            return;
        }

        $this->lierLesParents();
        $this->chiffrerEtEncaisser($annee);
        $this->saisirLesNotes($annee);
    }

    /**
     * Chaque élève reçoit un à deux responsables légaux, dont un contact principal.
     */
    private function lierLesParents(): void
    {
        $parents = ParentModel::all();

        if ($parents->isEmpty()) {
            $this->command->warn('Aucun parent en base : liens ignorés.');
            return;
        }

        $liens = [];
        $maintenant = now();

        /*
         * Le lien decoule du sexe du parent : un homme est pere, une femme est
         * mere. Tirer le lien au hasard pour chaque enfant produisait des
         * aberrations — un meme adulte pere, mere et tuteur a la fois.
         *
         * Un adulte sur cinq est tuteur plutot que parent biologique : c'est
         * le cas reel qui justifie de porter le lien sur le rattachement.
         */
        $roleParParent = $parents->mapWithKeys(fn (ParentModel $parent) => [
            $parent->id => $parent->id % 5 === 0
                ? 'guardian'
                : ($parent->gender === 'female' ? 'mother' : 'father'),
        ]);

        foreach (Student::select('id')->get() as $index => $eleve) {
            // Deux responsables sur trois élèves, un seul sur le troisième.
            $nombre = $index % 3 === 2 ? 1 : 2;

            $choisis = $parents->random(min($nombre, $parents->count()));
            $choisis = $choisis instanceof ParentModel ? collect([$choisis]) : $choisis;

            foreach ($choisis->values() as $rang => $parent) {
                $liens[] = [
                    'student_id' => $eleve->id,
                    'parent_id' => $parent->id,
                    'relationship_type' => $roleParParent[$parent->id],
                    'is_primary_contact' => $rang === 0,
                    'lives_with_student' => true,
                    'can_pickup' => true,
                    'created_at' => $maintenant,
                    'updated_at' => $maintenant,
                ];
            }
        }

        // Un même couple élève/parent peut sortir deux fois du tirage.
        $liens = collect($liens)
            ->unique(fn (array $lien) => $lien['student_id'].'-'.$lien['parent_id'])
            ->values()
            ->all();

        // La table porte une contrainte d'unicite (student_id, parent_id) :
        // upsert pour que le seeder puisse etre rejoue.
        DB::table('student_parent')->upsert($liens, ['student_id', 'parent_id']);

        $this->command->info(count($liens).' liens parent-élève créés.');
    }

    /**
     * Chiffre chaque inscription à partir des frais du niveau, puis encaisse
     * une part variable : soldé, partiel ou impayé.
     */
    private function chiffrerEtEncaisser(AcademicYear $annee): void
    {
        // Les frais portent le nom du niveau ; on les indexe pour les retrouver.
        $frais = Fee::where('is_active', true)->get();

        $inscriptions = Enrollment::with('schoolClass.level')
            ->where('academic_year_id', $annee->id)
            ->get();

        if ($inscriptions->isEmpty()) {
            $this->command->warn('Aucune inscription à chiffrer.');
            return;
        }

        $paiements = [];
        $maintenant = now();
        $rang = 0;

        foreach ($inscriptions as $inscription) {
            $niveau = $inscription->schoolClass?->level;
            $montantDu = $this->fraisDuNiveau($frais, $niveau?->name, $niveau?->cycle);

            // Une inscription sur deux soldée, une sur trois partielle, le reste impayé.
            $part = match ($rang % 6) {
                0, 1, 2 => 1.0,
                3, 4 => round(mt_rand(30, 70) / 100, 2),
                default => 0.0,
            };

            $encaisse = round($montantDu * $part, 2);
            $reste = round($montantDu - $encaisse, 2);

            // Vocabulaire impose par la contrainte de la colonne :
            // pending, partial, completed, overdue.
            $statut = $encaisse <= 0 ? 'pending' : ($reste > 0 ? 'partial' : 'completed');

            $inscription->forceFill([
                'total_fees' => $montantDu,
                'amount_paid' => $encaisse,
                'balance_due' => $reste,
                'payment_status' => $statut,
                'payment_method' => $encaisse > 0 ? $this->moyenCoteInscription($rang) : null,
            ])->save();

            if ($encaisse > 0) {
                $paye = $maintenant->copy()->subDays(mt_rand(0, 300));

                $paiements[] = [
                    'transaction_id' => sprintf('TRX-%s-%06d', $annee->id, $inscription->id),
                    'enrollment_id' => $inscription->id,
                    'student_id' => $inscription->student_id,
                    'amount' => $encaisse,
                    'currency' => 'XAF',
                    'payment_type' => $inscription->is_reinscription ? 're_enrollment' : 'enrollment',
                    'payment_method' => $this->moyenCotePaiement($rang),
                    'status' => 'completed',
                    'payer_name' => trim(($inscription->applicant_first_name ?? '').' '.($inscription->applicant_last_name ?? '')) ?: 'Responsable légal',
                    'payer_phone' => '0'.mt_rand(60000000, 77999999),
                    'paid_at' => $paye,
                    'receipt_number' => sprintf('REC-%s-%06d', $paye->format('Y'), $inscription->id),
                    'created_at' => $paye,
                    'updated_at' => $paye,
                    /*
                     * Pose a la main : cette insertion passe par le
                     * constructeur de requetes, qui ignore le trait chargé de
                     * rattacher le modele a son etablissement. Les 600
                     * paiements de demonstration n'appartenaient a personne.
                     */
                    'school_id' => $inscription->school_id ?? \App\Support\EcoleCourante::id(),
                ];
            }

            $rang++;
        }

        foreach (array_chunk($paiements, 200) as $lot) {
            DB::table('payments')->insert($lot);
        }

        $this->command->info(count($paiements).' paiements encaissés sur '.$inscriptions->count().' inscriptions.');
    }

    /**
     * Notes des trois trimestres pour les élèves du secondaire, matière par matière.
     */
    private function saisirLesNotes(AcademicYear $annee): void
    {
        $enseignants = Teacher::select('id')->get();

        if ($enseignants->isEmpty()) {
            $this->command->warn('Aucun enseignant : notes ignorées.');
            return;
        }

        // Le préprimaire et le primaire sont évalués par compétences, pas par notes.
        $inscriptions = Enrollment::with('schoolClass.level')
            ->where('academic_year_id', $annee->id)
            ->whereHas('schoolClass.level', fn ($q) => $q->whereIn('cycle', ['college', 'lycee']))
            ->limit(120)
            ->get();

        if ($inscriptions->isEmpty()) {
            $this->command->warn('Aucune inscription au secondaire : notes ignorées.');
            return;
        }

        $matieresParCycle = Subject::where('is_active', true)
            ->get()
            ->groupBy('cycle');

        $trimestres = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
        $notes = [];
        $maintenant = now();

        foreach ($inscriptions as $inscription) {
            $cycle = $inscription->schoolClass?->level?->cycle;
            $matieres = ($matieresParCycle[$cycle] ?? collect())->take(8);

            if ($matieres->isEmpty()) {
                continue;
            }

            // Chaque élève a un niveau propre : les notes gravitent autour.
            $moyennePersonnelle = mt_rand(80, 150) / 10;

            foreach ($matieres as $matiere) {
                foreach ($trimestres as $trimestre) {
                    $note = $moyennePersonnelle + (mt_rand(-30, 30) / 10);
                    $note = max(0, min(20, round($note, 2)));

                    $notes[] = [
                        'student_id' => $inscription->student_id,
                        'subject_id' => $matiere->id,
                        'class_id' => $inscription->class_id,
                        'academic_year_id' => $annee->id,
                        'teacher_id' => $enseignants->random()->id,
                        'term' => $trimestre,
                        'score' => $note,
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

        $this->command->info(count($notes).' notes saisies pour '.$inscriptions->count().' élèves du secondaire.');
    }

    /**
     * Retrouve scolarité + inscription pour un niveau donné, avec repli par cycle
     * si aucun frais ne porte le nom du niveau.
     */
    private function fraisDuNiveau($frais, ?string $niveau, ?string $cycle): float
    {
        if ($niveau) {
            $correspondants = $frais->filter(
                fn ($f) => str_contains(mb_strtolower($f->name), mb_strtolower($niveau))
            );

            if ($correspondants->isNotEmpty()) {
                return (float) $correspondants->sum('amount');
            }
        }

        return match ($cycle) {
            'preprimaire' => 135000.0,
            'primaire' => 165000.0,
            'college' => 240000.0,
            'lycee' => 320000.0,
            default => 180000.0,
        };
    }

    /**
     * Les deux tables n'emploient pas le meme vocabulaire pour le moyen de
     * paiement : `payments` distingue les operateurs mobiles, `enrollments`
     * les regroupe sous « mobile_money ». Les deux methodes restent alignees
     * sur le meme rang pour qu'une inscription et son paiement concordent.
     */
    private function moyenCotePaiement(int $rang): string
    {
        return ['cash', 'airtel_money', 'moov_money', 'bank_transfer'][$rang % 4];
    }

    private function moyenCoteInscription(int $rang): string
    {
        return ['cash', 'mobile_money', 'mobile_money', 'bank_transfer'][$rang % 4];
    }
}
