<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class ClassTeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "\n👨‍🏫 Assignation des professeurs aux classes...\n\n";

        // Supprimer les assignations existantes
        DB::table('class_teacher')->truncate();
        echo "🗑️  Assignations existantes supprimées\n\n";

        // Récupérer toutes les classes actives
        $classes = SchoolClass::where('is_active', true)->get();
        
        if ($classes->isEmpty()) {
            echo "❌ Aucune classe active trouvée.\n";
            return;
        }

        // Récupérer tous les professeurs actifs
        $teachers = Teacher::where('status', 'active')->get();
        
        if ($teachers->isEmpty()) {
            echo "❌ Aucun professeur actif trouvé.\n";
            return;
        }

        echo "📚 Classes trouvées : " . $classes->count() . "\n";
        echo "👨‍🏫 Professeurs trouvés : " . $teachers->count() . "\n\n";

        $assignments = [];

        foreach ($classes as $class) {
            // Trouver des professeurs compatibles avec le niveau de la classe
            $compatibleTeachers = $teachers->filter(function ($teacher) use ($class) {
                // Vérifier si le professeur peut enseigner dans ce cycle
                return $teacher->cycle === $class->level->cycle;
            });

            if ($compatibleTeachers->isEmpty()) {
                echo "⚠️  Aucun professeur compatible trouvé pour la classe {$class->name}\n";
                continue;
            }

            // Assigner un professeur principal (premier professeur compatible)
            $principalTeacher = $compatibleTeachers->first();
            
            $assignments[] = [
                'class_id' => $class->id,
                'teacher_id' => $principalTeacher->id,
                'role' => 'principal',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            echo "✅ {$class->name} ({$class->level->name}) → {$principalTeacher->first_name} {$principalTeacher->last_name} (Principal)\n";

            // Assigner 1-2 autres professeurs comme enseignants ordinaires
            $otherTeachers = $compatibleTeachers->skip(1)->take(2);
            
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
        } else {
            echo "\n❌ Aucune assignation créée.\n";
        }

        echo "\n📊 Résumé des assignations:\n";
        
        // Afficher le résumé par classe
        foreach ($classes as $class) {
            $classTeachers = DB::table('class_teacher')
                ->join('teachers', 'class_teacher.teacher_id', '=', 'teachers.id')
                ->where('class_teacher.class_id', $class->id)
                ->select('teachers.first_name', 'teachers.last_name', 'class_teacher.role')
                ->get();

            echo "\n🏫 {$class->name} ({$class->level->name}):\n";
            foreach ($classTeachers as $ct) {
                $roleLabel = $ct->role === 'principal' ? '👑 Principal' : '👨‍🏫 Enseignant';
                echo "   • {$ct->first_name} {$ct->last_name} - {$roleLabel}\n";
            }
        }

        echo "\n💡 Les professeurs principaux sont maintenant correctement assignés aux classes!\n";
    }
}
