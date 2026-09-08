<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\Level;

class EnrollmentStatusTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Ce seeder crée des données de test pour démontrer tous les cas d'usage
     * du système de gestion du statut des élèves (nouveau/redoublant/passant)
     */
    public function run(): void
    {
        // Récupérer ou créer les années scolaires
        $previousYear = AcademicYear::firstOrCreate(
            ['name' => '2023-2024'],
            [
                'start_date' => '2023-09-01',
                'end_date' => '2024-06-30',
                'is_current' => false
            ]
        );
        
        $currentYear = AcademicYear::firstOrCreate(
            ['name' => '2024-2025'],
            [
                'start_date' => '2024-09-01',
                'end_date' => '2025-06-30',
                'is_current' => true
            ]
        );
        
        // Récupérer ou créer les niveaux - PRIMAIRE
        $niveauCP = Level::firstOrCreate(
            ['name' => 'CP'],
            [
                'code' => 'CP',
                'cycle' => 'primaire',
                'order' => 1,
                'is_active' => true
            ]
        );
        
        $niveauCE1 = Level::firstOrCreate(
            ['name' => 'CE1'],
            [
                'code' => 'CE1',
                'cycle' => 'primaire',
                'order' => 2,
                'is_active' => true
            ]
        );
        
        // Récupérer ou créer les niveaux - COLLÈGE
        $niveau6eme = Level::firstOrCreate(
            ['name' => '6ème'],
            [
                'code' => '6EME',
                'cycle' => 'college',
                'order' => 1,
                'is_active' => true
            ]
        );
        
        $niveau5eme = Level::firstOrCreate(
            ['name' => '5ème'],
            [
                'code' => '5EME',
                'cycle' => 'college',
                'order' => 2,
                'is_active' => true
            ]
        );
        
        // Récupérer ou créer les niveaux - LYCÉE
        $niveauSeconde = Level::where('code', '2NDE')->first();
        if (!$niveauSeconde) {
            $niveauSeconde = Level::firstOrCreate(
                ['name' => 'Seconde'],
                [
                    'code' => '2NDE',
                    'cycle' => 'lycee',
                    'order' => 1,
                    'is_active' => true
                ]
            );
        }
        
        $niveauPremiere = Level::where('code', '1ERE')->first();
        if (!$niveauPremiere) {
            $niveauPremiere = Level::firstOrCreate(
                ['name' => 'Première'],
                [
                    'code' => '1ERE',
                    'cycle' => 'lycee',
                    'order' => 2,
                    'is_active' => true
                ]
            );
        }
        
        // Récupérer ou créer les classes - PRIMAIRE
        $classeCP = SchoolClass::firstOrCreate(
            ['name' => 'CP A', 'level_id' => $niveauCP->id],
            [
                'capacity' => 30,
                'is_active' => true
            ]
        );
        
        $classeCE1 = SchoolClass::firstOrCreate(
            ['name' => 'CE1 A', 'level_id' => $niveauCE1->id],
            [
                'capacity' => 30,
                'is_active' => true
            ]
        );
        
        // Récupérer ou créer les classes - COLLÈGE
        $classe6eme = SchoolClass::firstOrCreate(
            ['name' => '6ème A', 'level_id' => $niveau6eme->id],
            [
                'capacity' => 35,
                'is_active' => true
            ]
        );
        
        $classe5eme = SchoolClass::firstOrCreate(
            ['name' => '5ème A', 'level_id' => $niveau5eme->id],
            [
                'capacity' => 35,
                'is_active' => true
            ]
        );
        
        // Récupérer ou créer les classes - LYCÉE
        $classeSeconde = SchoolClass::firstOrCreate(
            ['name' => 'Seconde A', 'level_id' => $niveauSeconde->id],
            [
                'capacity' => 40,
                'is_active' => true
            ]
        );
        
        $classePremiere = SchoolClass::firstOrCreate(
            ['name' => 'Première A', 'level_id' => $niveauPremiere->id],
            [
                'capacity' => 40,
                'is_active' => true
            ]
        );
        
        // Récupérer ou créer quelques matières pour créer des notes
        $subjects = Subject::take(5)->get();
        
        if ($subjects->isEmpty()) {
            // Créer des matières de base
            $subjectNames = ['Français', 'Mathématiques', 'Sciences', 'Histoire-Géographie', 'Anglais'];
            $subjects = collect();
            
            foreach ($subjectNames as $name) {
                $subjects->push(Subject::firstOrCreate(
                    ['name' => $name],
                    [
                        'code' => strtoupper(substr($name, 0, 3)),
                        'coefficient' => 1,
                        'is_active' => true
                    ]
                ));
            }
        }
        
        // Récupérer un enseignant pour les notes (ou créer un enseignant de test)
        $teacher = \App\Models\Teacher::first();
        if (!$teacher) {
            $teacher = \App\Models\Teacher::create([
                'first_name' => 'Enseignant',
                'last_name' => 'Test',
                'email' => 'teacher.test@example.com',
                'phone' => '+241 00 00 00 00',
                'specialization' => 'Généraliste',
                'status' => 'active'
            ]);
        }
        
        $this->command->info('📚 Création des données de test pour le système de réinscription...');
        
        // ============================================
        // CAS 1: Élève PASSANT (admis avec bonne moyenne)
        // ============================================
        $this->command->info('');
        $this->command->info('✅ CAS 1: Élève PASSANT');
        
        $studentPassant = Student::firstOrCreate(
            ['student_id' => 'STU2024TEST001'],
            [
                'first_name' => 'Jean',
                'last_name' => 'PASSANT',
                'date_of_birth' => '2016-05-15',
                'gender' => 'male',
                'address' => 'Libreville, Gabon',
                'enrollment_date' => '2023-09-01',
                'status' => 'active'
            ]
        );
        
        // Inscription année précédente en CP
        $enrollmentPassantPrev = Enrollment::firstOrCreate(
            [
                'student_id' => $studentPassant->id,
                'class_id' => $classeCP->id,
                'academic_year_id' => $previousYear->id
            ],
            [
                'enrollment_date' => '2023-09-01',
                'status' => 'active',
                'is_new_enrollment' => true,
                'student_status' => 'nouveau',
                'applicant_first_name' => 'Jean',
                'applicant_last_name' => 'PASSANT',
                'applicant_date_of_birth' => '2016-05-15',
                'applicant_gender' => 'male',
                'applicant_address' => 'Libreville, Gabon'
            ]
        );
        
        // Créer des notes avec une bonne moyenne (15/20)
        if (Grade::where('student_id', $studentPassant->id)->where('term', '3ème trimestre')->where('academic_year_id', $previousYear->id)->count() == 0) {
            foreach ($subjects as $subject) {
                Grade::create([
                    'student_id' => $studentPassant->id,
                    'subject_id' => $subject->id,
                    'class_id' => $classeCP->id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $previousYear->id,
                    'term' => '3ème trimestre',
                    'score' => rand(14, 17),
                    'max_score' => 20,
                    'exam_date' => '2024-05-15'
                ]);
            }
        }
        
        $this->command->info("   - Élève: {$studentPassant->full_name} (Matricule: {$studentPassant->student_id})");
        $this->command->info("   - Classe précédente: {$classeCP->name}");
        $this->command->info("   - Moyenne: ~15/20 (Admis)");
        $this->command->info("   - Statut attendu: PASSANT (passage en {$classeCE1->name})");
        
        // ============================================
        // CAS 2: Élève REDOUBLANT (échec avec moyenne < 10)
        // ============================================
        $this->command->info('');
        $this->command->info('⚠️  CAS 2: Élève REDOUBLANT');
        
        $studentRedoublant = Student::firstOrCreate(
            ['student_id' => 'STU2024TEST002'],
            [
            'first_name' => 'Marie',
            'last_name' => 'REDOUBLANT',
            'date_of_birth' => '2016-08-20',
            'gender' => 'female',
            'address' => 'Port-Gentil, Gabon',
            'enrollment_date' => '2023-09-01',
            'status' => 'active'
        ]);
        
        // Inscription année précédente en CP
        $enrollmentRedoublantPrev = Enrollment::firstOrCreate(
            [
                'student_id' => $studentRedoublant->id,
                'class_id' => $classeCP->id,
                'academic_year_id' => $previousYear->id
            ],
            [
                'enrollment_date' => '2023-09-01',
                'status' => 'active',
                'is_new_enrollment' => true,
                'student_status' => 'nouveau',
                'applicant_first_name' => 'Marie',
                'applicant_last_name' => 'REDOUBLANT',
                'applicant_date_of_birth' => '2016-08-20',
                'applicant_gender' => 'female',
                'applicant_address' => 'Port-Gentil, Gabon'
            ]
        );
        
        // Créer des notes avec une mauvaise moyenne (7/20)
        if (Grade::where('student_id', $studentRedoublant->id)->where('term', '3ème trimestre')->where('academic_year_id', $previousYear->id)->count() == 0) {
            foreach ($subjects as $subject) {
                Grade::create([
                    'student_id' => $studentRedoublant->id,
                    'subject_id' => $subject->id,
                    'class_id' => $classeCP->id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $previousYear->id,
                    'term' => '3ème trimestre',
                    'score' => rand(5, 9),
                    'max_score' => 20,
                    'exam_date' => '2024-05-15'
                ]);
            }
        }
        
        $this->command->info("   - Élève: {$studentRedoublant->full_name} (Matricule: {$studentRedoublant->student_id})");
        $this->command->info("   - Classe précédente: {$classeCP->name}");
        $this->command->info("   - Moyenne: ~7/20 (Échec)");
        $this->command->info("   - Statut attendu: REDOUBLANT (redouble en {$classeCP->name})");
        
        // ============================================
        // CAS 3: NOUVEL ÉLÈVE (première inscription)
        // ============================================
        $this->command->info('');
        $this->command->info('🆕 CAS 3: NOUVEL ÉLÈVE');
        
        $studentNouveau = Student::firstOrCreate(
            ['student_id' => 'STU2024TEST003'],
            [
                'first_name' => 'Pierre',
                'last_name' => 'NOUVEAU',
                'date_of_birth' => '2017-03-10',
                'gender' => 'male',
                'address' => 'Franceville, Gabon',
                'enrollment_date' => '2024-09-01',
                'status' => 'active'
            ]
        );
        
        $this->command->info("   - Élève: {$studentNouveau->full_name} (Matricule: {$studentNouveau->student_id})");
        $this->command->info("   - Aucune inscription précédente");
        $this->command->info("   - Statut attendu: NOUVEAU");
        
        // ============================================
        // CAS 4: Élève admis mais inscrit dans une classe différente
        // ============================================
        $this->command->info('');
        $this->command->info('🔄 CAS 4: Élève admis mais classe différente');
        
        $studentDifferent = Student::firstOrCreate(
            ['student_id' => 'STU2024TEST004'],
            [
                'first_name' => 'Sophie',
                'last_name' => 'DIFFERENT',
                'date_of_birth' => '2016-11-25',
                'gender' => 'female',
                'address' => 'Oyem, Gabon',
                'enrollment_date' => '2023-09-01',
                'status' => 'active'
            ]
        );
        
        // Inscription année précédente en CE1
        $enrollmentDifferentPrev = Enrollment::firstOrCreate(
            [
                'student_id' => $studentDifferent->id,
                'class_id' => $classeCE1->id,
                'academic_year_id' => $previousYear->id
            ],
            [
                'enrollment_date' => '2023-09-01',
                'status' => 'active',
                'is_new_enrollment' => true,
                'student_status' => 'nouveau',
                'applicant_first_name' => 'Sophie',
                'applicant_last_name' => 'DIFFERENT',
                'applicant_date_of_birth' => '2016-11-25',
                'applicant_gender' => 'female',
                'applicant_address' => 'Oyem, Gabon'
            ]
        );
        
        // Créer des notes avec une bonne moyenne (13/20)
        if (Grade::where('student_id', $studentDifferent->id)->where('term', '3ème trimestre')->where('academic_year_id', $previousYear->id)->count() == 0) {
            foreach ($subjects as $subject) {
                Grade::create([
                    'student_id' => $studentDifferent->id,
                    'subject_id' => $subject->id,
                    'class_id' => $classeCE1->id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $previousYear->id,
                    'term' => '3ème trimestre',
                    'score' => rand(12, 15),
                    'max_score' => 20,
                    'exam_date' => '2024-05-15'
                ]);
            }
        }
        
        $this->command->info("   - Élève: {$studentDifferent->full_name} (Matricule: {$studentDifferent->student_id})");
        $this->command->info("   - Classe précédente: {$classeCE1->name}");
        $this->command->info("   - Moyenne: ~13/20 (Admis)");
        $this->command->info("   - Si inscrit en CP: Statut attendu: NOUVEAU (classe différente)");
        
        // ============================================
        // CAS 5: Élève avec moyenne limite (juste passant)
        // ============================================
        $this->command->info('');
        $this->command->info('⚖️  CAS 5: Élève avec moyenne limite');
        
        $studentLimite = Student::firstOrCreate(
            ['student_id' => 'STU2024TEST005'],
            [
                'first_name' => 'Lucas',
                'last_name' => 'LIMITE',
                'date_of_birth' => '2016-07-05',
                'gender' => 'male',
                'address' => 'Moanda, Gabon',
                'enrollment_date' => '2023-09-01',
                'status' => 'active'
            ]
        );
        
        // Inscription année précédente en CP
        $enrollmentLimitePrev = Enrollment::firstOrCreate(
            [
                'student_id' => $studentLimite->id,
                'class_id' => $classeCP->id,
                'academic_year_id' => $previousYear->id
            ],
            [
                'enrollment_date' => '2023-09-01',
                'status' => 'active',
                'is_new_enrollment' => true,
                'student_status' => 'nouveau',
                'applicant_first_name' => 'Lucas',
                'applicant_last_name' => 'LIMITE',
                'applicant_date_of_birth' => '2016-07-05',
                'applicant_gender' => 'male',
                'applicant_address' => 'Moanda, Gabon'
            ]
        );
        
        // Créer des notes avec une moyenne juste passante (10/20)
        if (Grade::where('student_id', $studentLimite->id)->where('term', '3ème trimestre')->where('academic_year_id', $previousYear->id)->count() == 0) {
            foreach ($subjects as $subject) {
                Grade::create([
                    'student_id' => $studentLimite->id,
                    'subject_id' => $subject->id,
                    'class_id' => $classeCP->id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $previousYear->id,
                    'term' => '3ème trimestre',
                    'score' => rand(9, 11),
                    'max_score' => 20,
                    'exam_date' => '2024-05-15'
                ]);
            }
        }
        
        $this->command->info("   - Élève: {$studentLimite->full_name} (Matricule: {$studentLimite->student_id})");
        $this->command->info("   - Classe précédente: {$classeCP->name}");
        $this->command->info("   - Moyenne: ~10/20 (Juste admis)");
        $this->command->info("   - Statut attendu: PASSANT (passage en {$classeCE1->name})");
        
        // ============================================
        // CAS 6: Élève COLLÈGE - PASSANT (6ème → 5ème)
        // ============================================
        $this->command->info('');
        $this->command->info('🎓 CAS 6: COLLÈGE - Élève PASSANT');
        
        $studentCollege = Student::firstOrCreate(
            ['student_id' => 'STU2024TEST006'],
            [
                'first_name' => 'Aminata',
                'last_name' => 'COLLEGE',
                'date_of_birth' => '2011-04-12',
                'gender' => 'female',
                'address' => 'Libreville, Gabon',
                'enrollment_date' => '2023-09-01',
                'status' => 'active'
            ]
        );
        
        // Inscription année précédente en 6ème
        $enrollmentCollegePrev = Enrollment::firstOrCreate(
            [
                'student_id' => $studentCollege->id,
                'class_id' => $classe6eme->id,
                'academic_year_id' => $previousYear->id
            ],
            [
                'enrollment_date' => '2023-09-01',
                'status' => 'active',
                'is_new_enrollment' => true,
                'student_status' => 'nouveau',
                'applicant_first_name' => 'Aminata',
                'applicant_last_name' => 'COLLEGE',
                'applicant_date_of_birth' => '2011-04-12',
                'applicant_gender' => 'female',
                'applicant_address' => 'Libreville, Gabon'
            ]
        );
        
        // Créer des notes avec une bonne moyenne (14/20)
        if (Grade::where('student_id', $studentCollege->id)->where('term', '3ème trimestre')->where('academic_year_id', $previousYear->id)->count() == 0) {
            foreach ($subjects as $subject) {
                Grade::create([
                    'student_id' => $studentCollege->id,
                    'subject_id' => $subject->id,
                    'class_id' => $classe6eme->id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $previousYear->id,
                    'term' => '3ème trimestre',
                    'score' => rand(13, 16),
                    'max_score' => 20,
                    'exam_date' => '2024-05-15'
                ]);
            }
        }
        
        $this->command->info("   - Élève: {$studentCollege->full_name} (Matricule: {$studentCollege->student_id})");
        $this->command->info("   - Classe précédente: {$classe6eme->name}");
        $this->command->info("   - Moyenne: ~14/20 (Admis)");
        $this->command->info("   - Statut attendu: PASSANT (passage en {$classe5eme->name})");
        
        // ============================================
        // CAS 7: Élève COLLÈGE - REDOUBLANT (6ème → 6ème)
        // ============================================
        $this->command->info('');
        $this->command->info('🎓 CAS 7: COLLÈGE - Élève REDOUBLANT');
        
        $studentCollegeRedouble = Student::firstOrCreate(
            ['student_id' => 'STU2024TEST007'],
            [
                'first_name' => 'Ibrahim',
                'last_name' => 'COLLEGEREDOUBLE',
                'date_of_birth' => '2011-09-18',
                'gender' => 'male',
                'address' => 'Port-Gentil, Gabon',
                'enrollment_date' => '2023-09-01',
                'status' => 'active'
            ]
        );
        
        // Inscription année précédente en 6ème
        $enrollmentCollegeRedoublePrev = Enrollment::firstOrCreate(
            [
                'student_id' => $studentCollegeRedouble->id,
                'class_id' => $classe6eme->id,
                'academic_year_id' => $previousYear->id
            ],
            [
                'enrollment_date' => '2023-09-01',
                'status' => 'active',
                'is_new_enrollment' => true,
                'student_status' => 'nouveau',
                'applicant_first_name' => 'Ibrahim',
                'applicant_last_name' => 'COLLEGEREDOUBLE',
                'applicant_date_of_birth' => '2011-09-18',
                'applicant_gender' => 'male',
                'applicant_address' => 'Port-Gentil, Gabon'
            ]
        );
        
        // Créer des notes avec une mauvaise moyenne (8/20)
        if (Grade::where('student_id', $studentCollegeRedouble->id)->where('term', '3ème trimestre')->where('academic_year_id', $previousYear->id)->count() == 0) {
            foreach ($subjects as $subject) {
                Grade::create([
                    'student_id' => $studentCollegeRedouble->id,
                    'subject_id' => $subject->id,
                    'class_id' => $classe6eme->id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $previousYear->id,
                    'term' => '3ème trimestre',
                    'score' => rand(6, 9),
                    'max_score' => 20,
                    'exam_date' => '2024-05-15'
                ]);
            }
        }
        
        $this->command->info("   - Élève: {$studentCollegeRedouble->full_name} (Matricule: {$studentCollegeRedouble->student_id})");
        $this->command->info("   - Classe précédente: {$classe6eme->name}");
        $this->command->info("   - Moyenne: ~8/20 (Échec)");
        $this->command->info("   - Statut attendu: REDOUBLANT (redouble en {$classe6eme->name})");
        
        // ============================================
        // CAS 8: Élève LYCÉE - PASSANT (Seconde → Première)
        // ============================================
        $this->command->info('');
        $this->command->info('🎓 CAS 8: LYCÉE - Élève PASSANT');
        
        $studentLycee = Student::firstOrCreate(
            ['student_id' => 'STU2024TEST008'],
            [
                'first_name' => 'Fatima',
                'last_name' => 'LYCEE',
                'date_of_birth' => '2008-06-22',
                'gender' => 'female',
                'address' => 'Libreville, Gabon',
                'enrollment_date' => '2023-09-01',
                'status' => 'active'
            ]
        );
        
        // Inscription année précédente en Seconde
        $enrollmentLyceePrev = Enrollment::firstOrCreate(
            [
                'student_id' => $studentLycee->id,
                'class_id' => $classeSeconde->id,
                'academic_year_id' => $previousYear->id
            ],
            [
                'enrollment_date' => '2023-09-01',
                'status' => 'active',
                'is_new_enrollment' => true,
                'student_status' => 'nouveau',
                'applicant_first_name' => 'Fatima',
                'applicant_last_name' => 'LYCEE',
                'applicant_date_of_birth' => '2008-06-22',
                'applicant_gender' => 'female',
                'applicant_address' => 'Libreville, Gabon'
            ]
        );
        
        // Créer des notes avec une bonne moyenne (13/20)
        if (Grade::where('student_id', $studentLycee->id)->where('term', '3ème trimestre')->where('academic_year_id', $previousYear->id)->count() == 0) {
            foreach ($subjects as $subject) {
                Grade::create([
                    'student_id' => $studentLycee->id,
                    'subject_id' => $subject->id,
                    'class_id' => $classeSeconde->id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $previousYear->id,
                    'term' => '3ème trimestre',
                    'score' => rand(12, 15),
                    'max_score' => 20,
                    'exam_date' => '2024-05-15'
                ]);
            }
        }
        
        $this->command->info("   - Élève: {$studentLycee->full_name} (Matricule: {$studentLycee->student_id})");
        $this->command->info("   - Classe précédente: {$classeSeconde->name}");
        $this->command->info("   - Moyenne: ~13/20 (Admis)");
        $this->command->info("   - Statut attendu: PASSANT (passage en {$classePremiere->name})");
        
        // ============================================
        // CAS 9: Élève LYCÉE - REDOUBLANT (Seconde → Seconde)
        // ============================================
        $this->command->info('');
        $this->command->info('🎓 CAS 9: LYCÉE - Élève REDOUBLANT');
        
        $studentLyceeRedouble = Student::firstOrCreate(
            ['student_id' => 'STU2024TEST009'],
            [
                'first_name' => 'Olivier',
                'last_name' => 'LYCEEREDOUBLE',
                'date_of_birth' => '2008-11-30',
                'gender' => 'male',
                'address' => 'Franceville, Gabon',
                'enrollment_date' => '2023-09-01',
                'status' => 'active'
            ]
        );
        
        // Inscription année précédente en Seconde
        $enrollmentLyceeRedoublePrev = Enrollment::firstOrCreate(
            [
                'student_id' => $studentLyceeRedouble->id,
                'class_id' => $classeSeconde->id,
                'academic_year_id' => $previousYear->id
            ],
            [
                'enrollment_date' => '2023-09-01',
                'status' => 'active',
                'is_new_enrollment' => true,
                'student_status' => 'nouveau',
                'applicant_first_name' => 'Olivier',
                'applicant_last_name' => 'LYCEEREDOUBLE',
                'applicant_date_of_birth' => '2008-11-30',
                'applicant_gender' => 'male',
                'applicant_address' => 'Franceville, Gabon'
            ]
        );
        
        // Créer des notes avec une mauvaise moyenne (6/20)
        if (Grade::where('student_id', $studentLyceeRedouble->id)->where('term', '3ème trimestre')->where('academic_year_id', $previousYear->id)->count() == 0) {
            foreach ($subjects as $subject) {
                Grade::create([
                    'student_id' => $studentLyceeRedouble->id,
                    'subject_id' => $subject->id,
                    'class_id' => $classeSeconde->id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $previousYear->id,
                    'term' => '3ème trimestre',
                    'score' => rand(4, 8),
                    'max_score' => 20,
                    'exam_date' => '2024-05-15'
                ]);
            }
        }
        
        $this->command->info("   - Élève: {$studentLyceeRedouble->full_name} (Matricule: {$studentLyceeRedouble->student_id})");
        $this->command->info("   - Classe précédente: {$classeSeconde->name}");
        $this->command->info("   - Moyenne: ~6/20 (Échec)");
        $this->command->info("   - Statut attendu: REDOUBLANT (redouble en {$classeSeconde->name})");
        
        // ============================================
        // Mise à jour des statistiques de tous les élèves
        // ============================================
        $this->command->info('');
        $this->command->info('🔄 Mise à jour des statistiques des élèves...');
        
        $allTestStudents = [
            $studentPassant,
            $studentRedoublant,
            $studentNouveau,
            $studentDifferent,
            $studentLimite,
            $studentCollege,
            $studentCollegeRedouble,
            $studentLycee,
            $studentLyceeRedouble
        ];
        
        foreach ($allTestStudents as $student) {
            $student->updateEnrollmentStats();
            $this->command->info("   ✓ {$student->full_name} - Statut: {$student->current_status} - Inscriptions: {$student->total_enrollments}");
        }
        
        // ============================================
        // Résumé
        // ============================================
        $this->command->info('');
        $this->command->info('════════════════════════════════════════════════════════════');
        $this->command->info('📊 RÉSUMÉ DES CAS DE TEST');
        $this->command->info('════════════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('Pour tester le système de réinscription:');
        $this->command->info('');
        $this->command->info('1. Allez sur /enrollments/create');
        $this->command->info('2. Décochez "Nouvel élève (première inscription)"');
        $this->command->info('3. Entrez un des matricules suivants:');
        $this->command->info('');
        $this->command->info('🎒 PRIMAIRE:');
        $this->command->info("   📗 STU2024TEST001 → PASSANT (CP → CE1, moyenne 15/20)");
        $this->command->info("   📕 STU2024TEST002 → REDOUBLANT (CP → CP, moyenne 7/20)");
        $this->command->info("   📘 STU2024TEST003 → NOUVEAU (première inscription)");
        $this->command->info("   📙 STU2024TEST004 → NOUVEAU (CE1, classe différente)");
        $this->command->info("   📓 STU2024TEST005 → PASSANT (CP → CE1, moyenne 10/20)");
        $this->command->info('');
        $this->command->info('🎓 COLLÈGE:');
        $this->command->info("   📗 STU2024TEST006 → PASSANT (6ème → 5ème, moyenne 14/20)");
        $this->command->info("   📕 STU2024TEST007 → REDOUBLANT (6ème → 6ème, moyenne 8/20)");
        $this->command->info('');
        $this->command->info('🎓 LYCÉE:');
        $this->command->info("   📗 STU2024TEST008 → PASSANT (Seconde → Première, moyenne 13/20)");
        $this->command->info("   📕 STU2024TEST009 → REDOUBLANT (Seconde → Seconde, moyenne 6/20)");
        $this->command->info('');
        $this->command->info('4. Sélectionnez une classe et cliquez sur "Vérifier"');
        $this->command->info('5. Le système affichera:');
        $this->command->info('   - Statut détecté (Nouveau/Redoublant/Passant)');
        $this->command->info('   - Statut actuel (Actif/Ancien élève)');
        $this->command->info('   - Total des inscriptions');
        $this->command->info('   - Nombre de redoublements');
        $this->command->info('   - Historique complet des inscriptions');
        $this->command->info('');
        $this->command->info('════════════════════════════════════════════════════════════');
    }
}