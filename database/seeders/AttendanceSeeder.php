<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\SchoolClass;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer quelques classes
        $classes = SchoolClass::with('students')->take(3)->get();
        
        if ($classes->isEmpty()) {
            $this->command->info('Aucune classe trouvée. Veuillez d\'abord exécuter les seeders des classes et étudiants.');
            return;
        }
        
        $this->command->info('Création des données de test pour les présences...');
        
        foreach ($classes as $class) {
            // Récupérer les étudiants de la classe pour l'année académique courante
            $currentAcademicYear = \App\Models\AcademicYear::where('is_current', true)->first();
            if (!$currentAcademicYear) {
                continue;
            }
            
            $students = $class->students()
                ->wherePivot('academic_year_id', $currentAcademicYear->id)
                ->wherePivot('status', 'active')
                ->get();
                
            if ($students->isEmpty()) {
                continue;
            }
            
            // Générer des présences pour les 7 derniers jours
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                
                // Ne pas créer de présences pour les weekends
                if ($date->isWeekend()) {
                    continue;
                }
                
                foreach ($class->students as $student) {
                    // 85% de chance d'être présent
                    $isPresent = rand(1, 100) <= 85;
                    
                    if ($isPresent) {
                        // 90% de chance d'être à l'heure, 10% en retard
                        $isLate = rand(1, 100) <= 10;
                        $status = $isLate ? 'late' : 'present';
                        
                        // Heure d'arrivée (entre 7h30 et 8h30, ou plus tard si en retard)
                        $arrivalTime = $isLate 
                            ? Carbon::createFromTime(8, rand(30, 59), rand(0, 59))
                            : Carbon::createFromTime(7, rand(30, 59), rand(0, 59));
                        
                        Attendance::create([
                            'student_id' => $student->id,
                            'class_id' => $class->id,
                            'attendance_date' => $date,
                            'status' => $status,
                            'arrival_time' => $arrivalTime->format('H:i:s'),
                            'reason' => $isLate ? 'Retard de transport' : null,
                            'justified' => $isLate ? rand(0, 1) : false
                        ]);
                    } else {
                        // Absent ou excusé
                        $isExcused = rand(1, 100) <= 30; // 30% des absents sont excusés
                        $status = $isExcused ? 'excused' : 'absent';
                        
                        $reasons = [
                            'absent' => ['Maladie', 'Problème familial', 'Transport indisponible', 'Rendez-vous médical'],
                            'excused' => ['Maladie avec certificat', 'Décès dans la famille', 'Cérémonie religieuse', 'Compétition sportive']
                        ];
                        
                        Attendance::create([
                            'student_id' => $student->id,
                            'class_id' => $class->id,
                            'attendance_date' => $date,
                            'status' => $status,
                            'arrival_time' => null,
                            'reason' => $reasons[$status][array_rand($reasons[$status])],
                            'justified' => $isExcused
                        ]);
                    }
                }
            }
        }
        
        $this->command->info('Données de test des présences créées avec succès !');
    }
}
