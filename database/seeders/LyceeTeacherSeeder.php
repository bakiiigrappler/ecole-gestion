<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class LyceeTeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "\n👨‍🏫 Création des professeurs pour le lycée...\n\n";

        // Professeurs pour le lycée
        $lyceeTeachers = [
            [
                'employee_id' => 'ENS2025LYC001',
                'first_name' => 'Marie',
                'last_name' => 'Dupont',
                'email' => 'marie.dupont.lycee@ecole.edu',
                'phone' => '+241 01 23 45 67',
                'date_of_birth' => '1985-03-15',
                'gender' => 'female',
                'address' => 'Libreville, Gabon',
                'qualification' => 'Master en Mathématiques',
                'specialization' => 'Mathématiques',
                'cycle' => 'lycee',
                'teacher_type' => 'specialized',
                'hire_date' => '2020-09-01',
                'salary' => 450000,
                'status' => 'active'
            ],
            [
                'employee_id' => 'ENS2025LYC002',
                'first_name' => 'Jean',
                'last_name' => 'Martin',
                'email' => 'jean.martin.lycee@ecole.edu',
                'phone' => '+241 01 23 45 68',
                'date_of_birth' => '1982-07-20',
                'gender' => 'male',
                'address' => 'Libreville, Gabon',
                'qualification' => 'Master en Physique-Chimie',
                'specialization' => 'Sciences Physiques',
                'cycle' => 'lycee',
                'teacher_type' => 'specialized',
                'hire_date' => '2019-09-01',
                'salary' => 450000,
                'status' => 'active'
            ],
            [
                'employee_id' => 'ENS2025LYC003',
                'first_name' => 'Sophie',
                'last_name' => 'Bernard',
                'email' => 'sophie.bernard.lycee@ecole.edu',
                'phone' => '+241 01 23 45 69',
                'date_of_birth' => '1988-11-10',
                'gender' => 'female',
                'address' => 'Libreville, Gabon',
                'qualification' => 'Master en Lettres Modernes',
                'specialization' => 'Français',
                'cycle' => 'lycee',
                'teacher_type' => 'specialized',
                'hire_date' => '2021-09-01',
                'salary' => 450000,
                'status' => 'active'
            ],
            [
                'employee_id' => 'ENS2025LYC004',
                'first_name' => 'Pierre',
                'last_name' => 'Moreau',
                'email' => 'pierre.moreau.lycee@ecole.edu',
                'phone' => '+241 01 23 45 70',
                'date_of_birth' => '1980-05-25',
                'gender' => 'male',
                'address' => 'Libreville, Gabon',
                'qualification' => 'Master en Histoire-Géographie',
                'specialization' => 'Histoire-Géographie',
                'cycle' => 'lycee',
                'teacher_type' => 'specialized',
                'hire_date' => '2018-09-01',
                'salary' => 450000,
                'status' => 'active'
            ],
            [
                'employee_id' => 'ENS2025LYC005',
                'first_name' => 'Claire',
                'last_name' => 'Petit',
                'email' => 'claire.petit.lycee@ecole.edu',
                'phone' => '+241 01 23 45 71',
                'date_of_birth' => '1986-09-12',
                'gender' => 'female',
                'address' => 'Libreville, Gabon',
                'qualification' => 'Master en Biologie',
                'specialization' => 'Sciences de la Vie et de la Terre',
                'cycle' => 'lycee',
                'teacher_type' => 'specialized',
                'hire_date' => '2020-09-01',
                'salary' => 450000,
                'status' => 'active'
            ],
            [
                'employee_id' => 'ENS2025LYC006',
                'first_name' => 'Antoine',
                'last_name' => 'Rousseau',
                'email' => 'antoine.rousseau.lycee@ecole.edu',
                'phone' => '+241 01 23 45 72',
                'date_of_birth' => '1983-12-08',
                'gender' => 'male',
                'address' => 'Libreville, Gabon',
                'qualification' => 'Master en Anglais',
                'specialization' => 'Anglais',
                'cycle' => 'lycee',
                'teacher_type' => 'specialized',
                'hire_date' => '2019-09-01',
                'salary' => 450000,
                'status' => 'active'
            ]
        ];

        foreach ($lyceeTeachers as $teacherData) {
            // Vérifier si le professeur existe déjà
            $existingTeacher = Teacher::where('employee_id', $teacherData['employee_id'])
                ->orWhere('email', $teacherData['email'])
                ->first();
            
            if ($existingTeacher) {
                echo "⚠️  {$teacherData['first_name']} {$teacherData['last_name']} existe déjà\n";
                continue;
            }
            
            $teacher = Teacher::create($teacherData);
            echo "✅ {$teacher->first_name} {$teacher->last_name} - {$teacher->specialization}\n";
        }

        echo "\n👨‍🏫 " . count($lyceeTeachers) . " professeurs du lycée créés avec succès!\n";

        // Maintenant, assigner ces professeurs aux classes du lycée
        echo "\n📚 Assignation des professeurs aux classes du lycée...\n";

        // Récupérer les classes du lycée
        $lyceeClasses = DB::table('classes')
            ->join('levels', 'classes.level_id', '=', 'levels.id')
            ->where('levels.cycle', 'lycee')
            ->where('classes.is_active', true)
            ->select('classes.id', 'classes.name', 'levels.name as level_name')
            ->get();

        if ($lyceeClasses->isEmpty()) {
            echo "❌ Aucune classe du lycée trouvée.\n";
            return;
        }

        // Récupérer les nouveaux professeurs du lycée
        $newLyceeTeachers = Teacher::where('cycle', 'lycee')->where('status', 'active')->get();

        $assignments = [];

        foreach ($lyceeClasses as $class) {
            // Assigner un professeur principal
            $principalTeacher = $newLyceeTeachers->first();
            
            $assignments[] = [
                'class_id' => $class->id,
                'teacher_id' => $principalTeacher->id,
                'role' => 'principal',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            echo "✅ {$class->name} ({$class->level_name}) → {$principalTeacher->first_name} {$principalTeacher->last_name} (Principal)\n";

            // Assigner 2 autres professeurs comme enseignants ordinaires
            $otherTeachers = $newLyceeTeachers->skip(1)->take(2);
            
            foreach ($otherTeachers as $teacher) {
                $assignments[] = [
                    'class_id' => $class->id,
                    'teacher_id' => $teacher->id,
                    'role' => 'teacher',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                echo "   └─ {$teacher->first_name} {$teacher->last_name} (Enseignant)\n";
            }
        }

        // Insérer toutes les assignations
        if (!empty($assignments)) {
            DB::table('class_teacher')->insert($assignments);
            echo "\n✅ " . count($assignments) . " assignations créées avec succès!\n";
        }

        echo "\n💡 Les professeurs du lycée sont maintenant correctement assignés aux classes!\n";
    }
}