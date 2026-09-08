<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PrePrimaryTestDataSeeder extends Seeder
{
    public function run()
    {
        echo "🌱 Création des données de test pour le préprimaire...\n\n";

        // 1. Année scolaire
        echo "📅 Création/récupération de l'année scolaire...\n";
        $existingYear = DB::table('academic_years')->where('name', '2024-2025')->first();
        if ($existingYear) {
            $academicYear = $existingYear->id;
            echo "   → Année 2024-2025 trouvée (ID: $academicYear)\n";
        } else {
            $academicYear = DB::table('academic_years')->insertGetId([
                'name' => '2024-2025',
                'start_date' => '2024-09-01',
                'end_date' => '2025-06-30',
                'is_current' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "   → Année 2024-2025 créée (ID: $academicYear)\n";
        }

        // 2. Niveaux de maternelle
        echo "📚 Récupération/création des niveaux de maternelle...\n";
        
        $psLevel = DB::table('levels')->where('code', 'PS')->first();
        if (!$psLevel) {
            $psLevel = (object)['id' => DB::table('levels')->insertGetId([
                'name' => 'Petite Section',
                'code' => 'PS',
                'cycle' => 'preprimaire',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ])];
        }
        $psLevel = $psLevel->id;

        $msLevel = DB::table('levels')->where('code', 'MS')->first();
        if (!$msLevel) {
            $msLevel = (object)['id' => DB::table('levels')->insertGetId([
                'name' => 'Moyenne Section',
                'code' => 'MS',
                'cycle' => 'preprimaire',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ])];
        }
        $msLevel = $msLevel->id;

        $gsLevel = DB::table('levels')->where('code', 'GS')->first();
        if (!$gsLevel) {
            $gsLevel = (object)['id' => DB::table('levels')->insertGetId([
                'name' => 'Grande Section',
                'code' => 'GS',
                'cycle' => 'preprimaire',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ])];
        }
        $gsLevel = $gsLevel->id;

        // 3. Enseignant
        echo "👨‍🏫 Récupération/création de l'enseignant...\n";
        $existingTeacher = DB::table('teachers')->where('email', 'marie.okome@egesco.com')->first();
        if ($existingTeacher) {
            $teacher = $existingTeacher->id;
            echo "   → Enseignante Marie OKOME trouvée (ID: $teacher)\n";
        } else {
            $teacher = DB::table('teachers')->insertGetId([
                'employee_id' => 'EMP' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                'first_name' => 'Marie',
                'last_name' => 'OKOME',
                'email' => 'marie.okome@egesco.com',
                'phone' => '+241 06 12 34 56',
                'gender' => 'female',
                'date_of_birth' => '1985-03-15',
                'hire_date' => '2020-09-01',
                'specialization' => 'Éducation Préscolaire',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "   → Enseignante Marie OKOME créée (ID: $teacher)\n";
        }

        // 4. Classes de maternelle
        echo "🏫 Création des classes...\n";
        $classPSA = DB::table('classes')->insertGetId([
            'name' => 'PS-A',
            'level_id' => $psLevel,
            'capacity' => 25,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $classMSA = DB::table('classes')->insertGetId([
            'name' => 'MS-A',
            'level_id' => $msLevel,
            'capacity' => 25,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $classGSA = DB::table('classes')->insertGetId([
            'name' => 'GS-A',
            'level_id' => $gsLevel,
            'capacity' => 25,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Associer l'enseignant aux classes
        DB::table('class_teacher')->insert([
            ['class_id' => $classPSA, 'teacher_id' => $teacher, 'role' => 'principal', 'created_at' => now(), 'updated_at' => now()],
            ['class_id' => $classMSA, 'teacher_id' => $teacher, 'role' => 'principal', 'created_at' => now(), 'updated_at' => now()],
            ['class_id' => $classGSA, 'teacher_id' => $teacher, 'role' => 'principal', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 5. Élèves
        echo "👶 Création des élèves...\n";
        $students = [
            // Petite Section
            ['first_name' => 'Jean', 'last_name' => 'MBENG', 'gender' => 'male', 'date_of_birth' => '2020-05-10', 'class_id' => $classPSA],
            ['first_name' => 'Sophie', 'last_name' => 'NGUEMA', 'gender' => 'female', 'date_of_birth' => '2020-07-15', 'class_id' => $classPSA],
            ['first_name' => 'Paul', 'last_name' => 'OBIANG', 'gender' => 'male', 'date_of_birth' => '2020-03-22', 'class_id' => $classPSA],
            
            // Moyenne Section
            ['first_name' => 'Marie', 'last_name' => 'KOUMBA', 'gender' => 'female', 'date_of_birth' => '2019-08-12', 'class_id' => $classMSA],
            ['first_name' => 'Kevin', 'last_name' => 'MOUNGUENGUI', 'gender' => 'male', 'date_of_birth' => '2019-11-05', 'class_id' => $classMSA],
            
            // Grande Section
            ['first_name' => 'Emma', 'last_name' => 'EYEGHE', 'gender' => 'female', 'date_of_birth' => '2018-09-20', 'class_id' => $classGSA],
            ['first_name' => 'Lucas', 'last_name' => 'MOUSSAVOU', 'gender' => 'male', 'date_of_birth' => '2018-12-08', 'class_id' => $classGSA],
        ];

        $studentIds = [];
        foreach ($students as $studentData) {
            $classId = $studentData['class_id'];
            unset($studentData['class_id']);
            
            $studentId = DB::table('students')->insertGetId(array_merge($studentData, [
                'student_id' => 'MAT' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                'address' => 'Libreville, Gabon',
                'enrollment_date' => '2024-09-01',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]));

            // Inscription
            DB::table('enrollments')->insert([
                'student_id' => $studentId,
                'class_id' => $classId,
                'academic_year_id' => $academicYear,
                'enrollment_date' => '2024-09-01',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $studentIds[] = ['id' => $studentId, 'class_id' => $classId, 'name' => $studentData['first_name'] . ' ' . $studentData['last_name']];
        }

        // 6. Évaluations préprimaires
        echo "📝 Création des évaluations...\n";
        
        // Récupérer toutes les compétences
        $competencies = DB::table('pre_primary_competencies')->get();
        
        $evaluationCodes = ['MAX', 'MIN', 'PART', 'NM'];
        
        foreach ($studentIds as $studentInfo) {
            $studentId = $studentInfo['id'];
            $classId = $studentInfo['class_id'];
            
            echo "  → Évaluations pour {$studentInfo['name']}\n";
            
            foreach ($competencies as $competency) {
                // Générer des codes aléatoires pour les 3 trimestres
                // Pour rendre les données réalistes, on fait progresser l'élève au fil des trimestres
                $trim1 = $evaluationCodes[rand(1, 3)]; // Plus de chances d'avoir MIN, PART ou NM
                
                // Trimestre 2 : légère amélioration
                if ($trim1 === 'NM') {
                    $trim2 = $evaluationCodes[rand(1, 3)];
                } elseif ($trim1 === 'PART') {
                    $trim2 = $evaluationCodes[rand(1, 2)];
                } elseif ($trim1 === 'MIN') {
                    $trim2 = rand(0, 1) ? 'MIN' : 'MAX';
                } else {
                    $trim2 = 'MAX';
                }
                
                // Trimestre 3 : meilleure performance
                if ($trim2 === 'NM') {
                    $trim3 = $evaluationCodes[rand(1, 2)];
                } elseif ($trim2 === 'PART') {
                    $trim3 = $evaluationCodes[rand(0, 2)];
                } elseif ($trim2 === 'MIN') {
                    $trim3 = rand(0, 1) ? 'MIN' : 'MAX';
                } else {
                    $trim3 = 'MAX';
                }

                DB::table('pre_primary_competency_evaluations')->insert([
                    'student_id' => $studentId,
                    'pre_primary_competency_id' => $competency->id,
                    'class_id' => $classId,
                    'academic_year_id' => $academicYear,
                    'teacher_id' => $teacher,
                    'trimester_1_code' => $trim1,
                    'trimester_2_code' => $trim2,
                    'trimester_3_code' => $trim3,
                    'trimester_1_comment' => $trim1 === 'MAX' ? 'Excellent travail !' : ($trim1 === 'NM' ? 'Non encore abordé.' : 'En cours d\'acquisition.'),
                    'trimester_2_comment' => $trim2 === 'MAX' ? 'Très bien !' : null,
                    'trimester_3_comment' => $trim3 === 'MAX' ? 'Compétence maîtrisée !' : null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        echo "\n✅ Données de test créées avec succès !\n";
        echo "📊 Résumé :\n";
        echo "   - 1 année scolaire (2024-2025)\n";
        echo "   - 3 niveaux de maternelle (PS, MS, GS)\n";
        echo "   - 3 classes (PS-A, MS-A, GS-A)\n";
        echo "   - 1 enseignante (Marie OKOME)\n";
        echo "   - 7 élèves répartis dans les 3 classes\n";
        echo "   - " . (count($studentIds) * count($competencies)) . " évaluations sur 48 compétences\n";
        echo "\n🎯 Vous pouvez maintenant tester les bulletins !\n";
    }
}

