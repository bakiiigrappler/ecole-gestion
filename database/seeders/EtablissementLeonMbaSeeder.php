<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Support\EcoleCourante;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Un second établissement : le Lycée National Léon Mba, à Libreville.
 *
 * Il sert à éprouver le multi-établissements sur un cas réel : un lycée qui
 * porte **le collège et le lycée seulement**, pas le préprimaire ni le
 * primaire. Ses données sont fictives — seuls le nom, la ville et la structure
 * des cycles s'inspirent de l'établissement existant.
 *
 * Tout ce que ce seeder crée est rattaché à cette école : `EcoleCourante` est
 * forcée le temps de l'exécution, et le trait `AppartientAUnEtablissement` s'en
 * charge ensuite tout seul.
 */
class EtablissementLeonMbaSeeder extends Seeder
{
    private const CODE = 'ETB002';

    /** Niveaux du second cycle, dans l'ordre de la scolarité. */
    private const NIVEAUX = [
        ['nom' => '6ème', 'code' => '6EME', 'cycle' => 'college', 'ordre' => 6, 'classes' => 2],
        ['nom' => '5ème', 'code' => '5EME', 'cycle' => 'college', 'ordre' => 7, 'classes' => 2],
        ['nom' => '4ème', 'code' => '4EME', 'cycle' => 'college', 'ordre' => 8, 'classes' => 2],
        ['nom' => '3ème', 'code' => '3EME', 'cycle' => 'college', 'ordre' => 9, 'classes' => 2],
        ['nom' => '2nde', 'code' => '2NDE', 'cycle' => 'lycee', 'ordre' => 10, 'classes' => 2],
        ['nom' => '1ère', 'code' => '1ERE', 'cycle' => 'lycee', 'ordre' => 11, 'classes' => 1],
        ['nom' => 'Terminale', 'code' => 'TLE', 'cycle' => 'lycee', 'ordre' => 12, 'classes' => 1],
    ];

    public function run(): void
    {
        $annee = AcademicYear::withoutGlobalScopes()->where('is_current', true)->first();

        if (! $annee) {
            $this->command->warn('Aucune année scolaire courante : établissement ignoré.');

            return;
        }

        if (School::where('code', self::CODE)->exists()) {
            $this->command->info('Le Lycée National Léon Mba existe déjà : rien à faire.');

            return;
        }

        $ecole = School::create([
            'name' => 'Lycée National Léon Mba',
            'code' => self::CODE,
            'has_preprimaire' => false,
            'has_primaire' => false,
            'has_college' => true,
            'has_lycee' => true,
            'is_active' => true,
            'city' => 'Libreville',
            'country' => 'Gabon',
            'address' => 'Quartier Nombakélé, Libreville',
            'phone' => '011 72 12 40',
            'email' => 'contact@lyceeleonmba.ga',
            'bp' => 'BP 2143',
            'notes' => 'Établissement public du second cycle : collège et lycée.',
        ]);

        // Tout ce qui suit lui appartient : le trait s'en charge dès lors que
        // l'établissement courant est posé.
        EcoleCourante::forcer($ecole->id);

        $this->parametres($ecole);
        $administrateur = $this->administrateur($ecole);
        $anneeLocale = $this->anneeScolaire($annee);
        $niveaux = $this->niveaux();
        $matieres = $this->matieres();
        $enseignants = $this->enseignants($matieres);
        $classes = $this->classes($niveaux, $enseignants);
        $eleves = $this->eleves($classes, $anneeLocale);

        EcoleCourante::oublier();

        $this->command->info(sprintf(
            'Lycée National Léon Mba créé : %d classes, %d élèves, %d enseignants, %d matières.',
            $classes->count(), $eleves, $enseignants->count(), $matieres->count()
        ));
        $this->command->info('  Administrateur : '.$administrateur->email.' / leonmba123');
    }

    private function parametres(School $ecole): void
    {
        SchoolSettings::create([
            'school_id' => $ecole->id,
            'school_name' => $ecole->name,
            'primary_school_name' => $ecole->name,
            'secondary_school_name' => $ecole->name,
            'school_address' => $ecole->address,
            'school_phone' => $ecole->phone,
            'school_email' => $ecole->email,
            'school_bp' => $ecole->bp,
            'school_motto' => 'Discipline — Travail — Réussite',
            'principal_name' => 'Le Proviseur',
            'principal_title' => 'Le Proviseur',
            'academic_year' => now()->year.'-'.(now()->year + 1),
            'school_type' => 'Lycée public',
            'school_level' => 'Collège et lycée',
            'has_preprimary' => false,
            'has_primary' => false,
            'has_secondary' => true,
            'city' => $ecole->city,
            'country' => $ecole->country,
            'timezone' => 'Africa/Libreville',
            'currency' => 'FCFA',
            'language' => 'fr',
            'is_active' => true,
        ]);
    }

    private function administrateur(School $ecole): User
    {
        return User::create([
            'name' => 'Proviseur Léon Mba',
            'email' => 'proviseur@lyceeleonmba.ga',
            'password' => Hash::make('leonmba123'),
            'role' => 'admin',
            'matricule' => $ecole->code.'-ADM',
            'school_id' => $ecole->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * L'année scolaire est rattachée à l'établissement : chacun tient la
     * sienne, même si les dates coïncident.
     */
    private function anneeScolaire(AcademicYear $modele): AcademicYear
    {
        return AcademicYear::create([
            'name' => $modele->name,
            'start_date' => $modele->start_date,
            'end_date' => $modele->end_date,
            'is_current' => true,
            'status' => 'active',
        ]);
    }

    private function niveaux()
    {
        return collect(self::NIVEAUX)->map(fn ($n) => Level::create([
            'name' => $n['nom'],
            'code' => $n['code'],
            'cycle' => $n['cycle'],
            'order' => $n['ordre'],
            'is_active' => true,
            'description' => $n['nom'].' — '.($n['cycle'] === 'college' ? 'collège' : 'lycée'),
        ])->setAttribute('nb_classes', $n['classes']));
    }

    private function matieres()
    {
        $referentiel = [
            ['Français', 'FR', 4, 'college'],
            ['Mathématiques', 'MATH', 4, 'college'],
            ['Anglais', 'ANG', 3, 'college'],
            ['Histoire-Géographie', 'HG', 3, 'college'],
            ['Sciences de la Vie et de la Terre', 'SVT', 3, 'college'],
            ['Sciences physiques', 'PC', 3, 'college'],
            ['Éducation physique et sportive', 'EPS', 2, 'college'],
            ['Philosophie', 'PHILO', 4, 'lycee'],
            ['Mathématiques', 'MATH-L', 5, 'lycee'],
            ['Sciences physiques', 'PC-L', 5, 'lycee'],
            ['Français', 'FR-L', 4, 'lycee'],
            ['Anglais', 'ANG-L', 3, 'lycee'],
            ['Histoire-Géographie', 'HG-L', 3, 'lycee'],
        ];

        return collect($referentiel)->map(fn ($m) => Subject::create([
            'name' => $m[0],
            'code' => $m[1],
            'coefficient' => $m[2],
            'cycle' => $m[3],
            'is_active' => true,
            'description' => $m[0],
        ]));
    }

    private function enseignants($matieres)
    {
        $noms = [
            ['Sylvain', 'Ondo', 'male', 'Mathématiques', 'college'],
            ['Georgette', 'Mba', 'female', 'Français', 'college'],
            ['Patrick', 'Nzé', 'male', 'Sciences physiques', 'college'],
            ['Chantal', 'Obame', 'female', 'Histoire-Géographie', 'college'],
            ['Rodrigue', 'Ella', 'male', 'Anglais', 'college'],
            ['Bernadette', 'Ndong', 'female', 'SVT', 'college'],
            ['Hervé', 'Moussavou', 'male', 'EPS', 'college'],
            ['Aline', 'Boussougou', 'female', 'Philosophie', 'lycee'],
            ['Serge', 'Mintsa', 'male', 'Mathématiques', 'lycee'],
            ['Pauline', 'Ivanga', 'female', 'Sciences physiques', 'lycee'],
            ['Fabrice', 'Koumba', 'male', 'Français', 'lycee'],
            ['Nadège', 'Bouyou', 'female', 'Anglais', 'lycee'],
        ];

        return collect($noms)->map(function ($n, $rang) use ($matieres) {
            $enseignant = Teacher::create([
                'employee_id' => 'LMB'.str_pad((string) ($rang + 1), 3, '0', STR_PAD_LEFT),
                'first_name' => $n[0],
                'last_name' => $n[1],
                'email' => strtolower($n[0].'.'.$n[1]).'@lyceeleonmba.ga',
                'phone' => '06'.random_int(1000000, 9999999),
                'gender' => $n[2],
                'date_of_birth' => Carbon::today()->subYears(random_int(30, 55)),
                'address' => 'Libreville',
                'qualification' => 'Licence',
                'specialization' => $n[3],
                'hire_date' => Carbon::today()->subYears(random_int(1, 15)),
                'status' => 'active',
                'cycle' => $n[4],
                'teacher_type' => 'specialized',
            ]);

            // Rattachement à sa matière : sans lui, l'emploi du temps ne sait
            // pas qui peut assurer quoi.
            $siennes = $matieres->filter(fn ($m) => str_contains($m->name, explode(' ', $n[3])[0])
                || $m->code === strtoupper(substr($n[3], 0, 3)));

            if ($siennes->isNotEmpty()) {
                $enseignant->subjects()->sync($siennes->pluck('id'));
            }

            return $enseignant;
        });
    }

    private function classes($niveaux, $enseignants)
    {
        $classes = collect();

        foreach ($niveaux as $niveau) {
            for ($i = 1; $i <= $niveau->nb_classes; $i++) {
                $classe = SchoolClass::create([
                    'name' => $niveau->name.' '.$i,
                    'level_id' => $niveau->id,
                    'capacity' => 40,
                    'is_active' => true,
                    'description' => 'Classe de '.$niveau->name,
                ]);

                // Un professeur principal par classe, pris dans le bon cycle.
                $principal = $enseignants->where('cycle', $niveau->cycle)->random();
                $classe->allTeachers()->syncWithoutDetaching([
                    $principal->id => ['role' => 'principal'],
                ]);

                $classes->push($classe);
            }
        }

        return $classes;
    }

    private function eleves($classes, AcademicYear $annee): int
    {
        $prenoms = ['Aristide', 'Bénédicte', 'Cédric', 'Diane', 'Emmanuel', 'Flore', 'Gaël',
                    'Henriette', 'Igor', 'Josiane', 'Landry', 'Murielle', 'Norbert', 'Olivia',
                    'Prosper', 'Rachelle', 'Sylvain', 'Thérèse', 'Ulrich', 'Vanessa'];
        $familles = ['Ondo', 'Mba', 'Nzé', 'Obame', 'Ella', 'Ndong', 'Moussavou', 'Boussougou',
                     'Mintsa', 'Ivanga', 'Koumba', 'Bouyou', 'Nguema', 'Bekale', 'Mengue'];

        $total = 0;
        $rang = 1;

        foreach ($classes as $classe) {
            $effectif = random_int(22, 34);

            for ($i = 0; $i < $effectif; $i++) {
                $sexe = random_int(0, 1) ? 'male' : 'female';

                $eleve = Student::create([
                    'student_id' => 'LMB'.str_pad((string) $rang++, 5, '0', STR_PAD_LEFT),
                    'first_name' => $prenoms[array_rand($prenoms)],
                    'last_name' => $familles[array_rand($familles)],
                    'date_of_birth' => Carbon::today()->subYears(random_int(11, 19))->subDays(random_int(0, 364)),
                    'gender' => $sexe,
                    'address' => 'Libreville',
                    'enrollment_date' => $annee->start_date,
                    'status' => 'active',
                ]);

                $eleve->enrollments()->create([
                    'class_id' => $classe->id,
                    'academic_year_id' => $annee->id,
                    'enrollment_date' => $annee->start_date,
                    'status' => 'active',
                    'payment_status' => ['completed', 'partial', 'pending'][random_int(0, 2)],
                    'school_id' => $eleve->school_id,
                ]);

                $total++;
            }
        }

        return $total;
    }
}
