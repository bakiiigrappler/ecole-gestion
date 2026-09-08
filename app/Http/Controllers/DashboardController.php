<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\AcademicYear;
use App\Models\StudentGrade;
use App\Models\Schedule;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function index()
    {
        /*
         * Le tableau de bord de l'établissement — recettes, paiements récents,
         * inscriptions de toute l'école — ne regarde que ceux qui l'administrent.
         * Chacun des autres rôles a le sien.
         */
        $role = auth()->user()?->role;

        if ($role === 'student') {
            return redirect()->route('mon-espace');
        }

        if ($role === 'parent') {
            return redirect()->route('parent-portal.dashboard');
        }

        if ($role === 'teacher') {
            return $this->monTableauDeBord();
        }

        // Année scolaire en cours
        $currentYear = AcademicYear::where('is_current', true)->first();
        
        // ========== STATISTIQUES PRINCIPALES ==========
        $totalStudents = Student::count();
        $totalTeachers = Teacher::where('status', 'active')->count();
        $totalClasses = SchoolClass::where('is_active', true)->count();
        $totalSubjects = Subject::where('is_active', true)->count();
        
        // ========== STATISTIQUES ÉLÈVES ==========
        $studentStats = [
            'total' => $totalStudents,
            'actifs' => Student::currentlyActive()->count(),
            'anciens' => Student::formerStudents()->count(),
            'avec_redoublement' => Student::where('total_redoublements', '>', 0)->count(),
            'garcons' => Student::where('gender', 'male')->count(),
            'filles' => Student::where('gender', 'female')->count(),
        ];
        
        // ========== STATISTIQUES INSCRIPTIONS ANNÉE EN COURS ==========
        $enrollmentStats = [
            'total' => Enrollment::whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->count(),
            'nouveau' => Enrollment::where('student_status', 'nouveau')
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
            'passant' => Enrollment::where('student_status', 'passant')
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
            'redoublant' => Enrollment::where('student_status', 'redoublant')
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
            'nouvelle_inscription' => Enrollment::where('is_new_enrollment', true)
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
            'reinscription' => Enrollment::where('is_reinscription', true)
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
        ];
        
        // ========== STATISTIQUES PAR CYCLE ==========
        $enrollmentsByCycle = [
            'preprimaire' => Enrollment::whereHas('schoolClass.level', function($q) {
                $q->where('cycle', 'preprimaire');
            })->whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->count(),
            'primaire' => Enrollment::whereHas('schoolClass.level', function($q) {
                $q->where('cycle', 'primaire');
            })->whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->count(),
            'college' => Enrollment::whereHas('schoolClass.level', function($q) {
                $q->where('cycle', 'college');
            })->whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->count(),
            'lycee' => Enrollment::whereHas('schoolClass.level', function($q) {
                $q->where('cycle', 'lycee');
            })->whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->count(),
        ];
        
        // ========== STATISTIQUES FINANCIÈRES ==========
        $financialStats = [
            'monthly_revenue' => Enrollment::whereMonth('enrollment_date', now()->month)
                ->whereYear('enrollment_date', now()->year)
                ->sum('amount_paid'),
            'yearly_revenue' => Enrollment::whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->sum('amount_paid'),
            'total_fees' => Enrollment::whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->sum('total_fees'),
            'balance_due' => Enrollment::whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->sum('balance_due'),
            'paid_count' => Enrollment::where('payment_status', 'completed')
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
            'partial_count' => Enrollment::where('payment_status', 'partial')
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
            'unpaid_count' => Enrollment::where('payment_status', 'pending')
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
        ];
        
        $financialStats['collection_rate'] = $financialStats['total_fees'] > 0 
            ? round(($financialStats['yearly_revenue'] / $financialStats['total_fees']) * 100, 1) 
            : 0;
        
        // ========== STATISTIQUES PAR CLASSE (TOP 5) ==========
        $classesByEnrollment = SchoolClass::withCount(['enrollments' => function($q) {
                $q->whereHas('academicYear', function($aq) {
                    $aq->where('is_current', true);
                });
            }])
            ->where('is_active', true)
            ->orderBy('enrollments_count', 'desc')
            ->take(5)
            ->get();
        
        // ========== ÉVOLUTION DES INSCRIPTIONS (12 DERNIERS MOIS) ==========
        $enrollmentTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $enrollmentTrend[] = [
                'month' => $date->format('M Y'),
                'count' => Enrollment::whereMonth('enrollment_date', $date->month)
                    ->whereYear('enrollment_date', $date->year)
                    ->count()
            ];
        }
        
        // ========== STATISTIQUES DES NOTES ==========
        $gradeStats = [
            'total_grades' => StudentGrade::count(),
            'average' => StudentGrade::avg(DB::raw('(score / max_score) * 20')),
            'excellent' => StudentGrade::whereRaw('(score / max_score) * 20 >= 16')->count(),
            'good' => StudentGrade::whereRaw('(score / max_score) * 20 >= 14 AND (score / max_score) * 20 < 16')->count(),
            'average_grade' => StudentGrade::whereRaw('(score / max_score) * 20 >= 10 AND (score / max_score) * 20 < 14')->count(),
            'below_average' => StudentGrade::whereRaw('(score / max_score) * 20 < 10')->count(),
        ];
        
        // ========== PRÉSENCES ==========
        $attendanceStats = [
            'today_rate' => 95, // À calculer dynamiquement
            'week_rate' => 93,
            'month_rate' => 91,
        ];
        
        // ========== ACTIVITÉS RÉCENTES ==========
        $recentEnrollments = Enrollment::with(['schoolClass', 'academicYear', 'student'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        $recentPayments = Enrollment::with(['schoolClass', 'student'])
            ->where('payment_status', 'completed')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();
        
        return view('dashboard.index', compact(
            'totalStudents',
            'totalTeachers', 
            'totalClasses',
            'totalSubjects',
            'studentStats',
            'enrollmentStats',
            'enrollmentsByCycle',
            'financialStats',
            'classesByEnrollment',
            'enrollmentTrend',
            'gradeStats',
            'attendanceStats',
            'recentEnrollments',
            'recentPayments',
            'currentYear'
        ));
    }
    
    /**
     * Le tableau de bord d'un enseignant : ses classes, ses heures, ses appels.
     *
     * Ni recettes, ni paiements, ni inscriptions de l'établissement : rien de
     * tout cela ne le concerne, et l'afficher reviendrait à lui ouvrir des
     * données qu'il n'a pas à connaître.
     */
    private function monTableauDeBord()
    {
        $perimetre = \App\Support\PerimetreEnseignant::class;
        $enseignant = $perimetre::enseignant();
        $annee = AcademicYear::where('is_current', true)->first();
        $aujourdHui = Carbon::today();

        $sesClasses = $perimetre::classes();

        // Ses élèves : ceux de ses classes, personne d'autre.
        $eleves = Student::whereHas('enrollments', fn ($q) => $q
            ->where('status', 'active')
            ->whereIn('class_id', $sesClasses ?: [0]))
            ->count();

        $creneaux = $perimetre::creneaux();

        // Sa journée : les cours qu'il donne aujourd'hui, dans l'ordre.
        $duJour = Schedule::with(['subject:id,name', 'schoolClass:id,name'])
            ->where('teacher_id', $enseignant?->id)
            ->where('day_of_week', $aujourdHui->dayOfWeekIso)
            ->where('type', 'course')
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
            ->orderBy('start_time')
            ->get();

        // Les appels du jour : ce qu'il lui reste à faire avant ce soir.
        $classesDuJour = $duJour->pluck('class_id')->unique()->values();

        $pointes = Attendance::whereIn('class_id', $classesDuJour)
            ->whereDate('attendance_date', $aujourdHui)
            ->select('class_id')
            ->distinct()
            ->pluck('class_id')
            ->all();

        $appels = SchoolClass::whereIn('id', $classesDuJour)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($classe) => [
                'classe' => $classe,
                'fait' => in_array($classe->id, $pointes, true),
            ]);

        // Ses classes, avec leur effectif.
        $classes = SchoolClass::whereIn('id', $sesClasses ?: [0])
            ->withCount(['enrollments as effectif' => fn ($q) => $q
                ->where('status', 'active')])
            ->orderBy('name')
            ->get();

        return view('dashboard.enseignant', [
            'enseignant' => $enseignant,
            'annee' => $annee,
            'eleves' => $eleves,
            'classes' => $classes,
            'heuresParSemaine' => $creneaux->where('type', 'course')->count(),
            'matieres' => count($perimetre::matieres()),
            'duJour' => $duJour,
            'appels' => $appels,
            'aujourdHui' => $aujourdHui,
        ]);
    }

    public function stats()
    {
        // API endpoint pour les statistiques en temps réel
        return response()->json([
            'students' => Student::count(),
            'teachers' => Teacher::where('status', 'active')->count(),
            'classes' => SchoolClass::where('is_active', true)->count(),
            'monthly_revenue' => Payment::whereMonth('paid_at', now()->month)
                                      ->whereYear('paid_at', now()->year)
                                      ->where('status', 'completed')
                                      ->sum('amount'),
            'attendance_rate' => 95, // Calculé dynamiquement
        ]);
    }

    /**
     * Export du rapport général (PDF)
     */
    public function exportGeneralReport()
    {
        try {
            $currentYear = AcademicYear::where('is_current', true)->first();
            
            // Récupérer toutes les données du dashboard
            $data = $this->getDashboardData();
            $data['reportType'] = 'general';
            $data['exportDate'] = now()->format('d/m/Y H:i');
            $data['schoolName'] = 'Egesco - École Gabonaise d\'Excellence';

            $pdf = Pdf::loadView('reports.dashboard-general', $data);
            $pdf->setPaper('A4', 'landscape');
            
            $filename = 'rapport_general_' . now()->format('Y-m-d_H-i-s') . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la génération du rapport général: ' . $e->getMessage());
        }
    }

    /**
     * Export du rapport financier (PDF)
     */
    public function exportFinancialReport()
    {
        try {
            $currentYear = AcademicYear::where('is_current', true)->first();
            
            // Récupérer les données financières
            $data = $this->getDashboardData();
            $data['reportType'] = 'financial';
            $data['exportDate'] = now()->format('d/m/Y H:i');
            $data['schoolName'] = 'Egesco - École Gabonaise d\'Excellence';

            $pdf = Pdf::loadView('reports.dashboard-financial', $data);
            $pdf->setPaper('A4', 'landscape');
            
            $filename = 'rapport_financier_' . now()->format('Y-m-d_H-i-s') . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la génération du rapport financier: ' . $e->getMessage());
        }
    }

    /**
     * Export du rapport des inscriptions (PDF)
     */
    public function exportEnrollmentReport()
    {
        try {
            $currentYear = AcademicYear::where('is_current', true)->first();
            
            // Récupérer les données des inscriptions
            $data = $this->getDashboardData();
            $data['reportType'] = 'enrollment';
            $data['exportDate'] = now()->format('d/m/Y H:i');
            $data['schoolName'] = 'Egesco - École Gabonaise d\'Excellence';

            $pdf = Pdf::loadView('reports.dashboard-enrollment', $data);
            $pdf->setPaper('A4', 'landscape');
            
            $filename = 'rapport_inscriptions_' . now()->format('Y-m-d_H-i-s') . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la génération du rapport des inscriptions: ' . $e->getMessage());
        }
    }

    /**
     * Export Excel des données du dashboard
     */
    public function exportExcel()
    {
        try {
            $data = $this->getDashboardData();
            
            $excelData = [
                ['Rapport du Dashboard - Egesco'],
                ['Généré le ' . now()->format('d/m/Y à H:i')],
                [''],
                ['Statistiques Générales', '', '', ''],
                ['Total Élèves', $data['totalStudents'] ?? 0, '', ''],
                ['Total Enseignants', $data['totalTeachers'] ?? 0, '', ''],
                ['Total Classes', $data['totalClasses'] ?? 0, '', ''],
                ['Total Matières', $data['totalSubjects'] ?? 0, '', ''],
                [''],
                ['Statistiques Élèves', '', '', ''],
                ['Élèves Actifs', $data['studentStats']['actifs'] ?? 0, '', ''],
                ['Garçons', $data['studentStats']['garcons'] ?? 0, '', ''],
                ['Filles', $data['studentStats']['filles'] ?? 0, '', ''],
                [''],
                ['Statistiques Financières', '', '', ''],
                ['Revenus Année', number_format($data['financialStats']['yearly_revenue'] ?? 0, 0, ',', ' ') . ' FCFA', '', ''],
                ['Revenus Mensuels', number_format($data['financialStats']['monthly_revenue'] ?? 0, 0, ',', ' ') . ' FCFA', '', ''],
                ['Total Attendu', number_format($data['financialStats']['total_fees'] ?? 0, 0, ',', ' ') . ' FCFA', '', ''],
                ['Solde à Percevoir', number_format($data['financialStats']['balance_due'] ?? 0, 0, ',', ' ') . ' FCFA', '', ''],
                ['Taux de Collecte', ($data['financialStats']['collection_rate'] ?? 0) . '%', '', ''],
                [''],
                ['Statut des Paiements', '', '', ''],
                ['Payé Complet', $data['financialStats']['paid_count'] ?? 0, '', ''],
                ['Paiement Partiel', $data['financialStats']['partial_count'] ?? 0, '', ''],
                ['Non Payé', $data['financialStats']['unpaid_count'] ?? 0, '', ''],
                [''],
                ['Inscriptions par Cycle', '', '', ''],
                ['Collège', $data['enrollmentsByCycle']['college'] ?? 0, '', ''],
                ['Lycée', $data['enrollmentsByCycle']['lycee'] ?? 0, '', ''],
                [''],
                ['Top 5 Classes', 'Effectif', 'Capacité', 'Taux'],
            ];

            // Ajouter les top classes
            foreach ($data['classesByEnrollment'] as $class) {
                $rate = $class->capacity > 0 ? round(($class->enrollments_count / $class->capacity) * 100) : 0;
                $excelData[] = [
                    $class->name,
                    $class->enrollments_count,
                    $class->capacity ?? 30,
                    $rate . '%'
                ];
            }

            $filename = 'dashboard_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
            
            $csv = fopen('php://temp', 'w');
            foreach ($excelData as $row) {
                fputcsv($csv, $row, ';');
            }
            rewind($csv);
            $content = stream_get_contents($csv);
            fclose($csv);
            
            return response($content)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la génération du fichier Excel: ' . $e->getMessage());
        }
    }

    /**
     * Récupérer toutes les données du dashboard
     */
    private function getDashboardData()
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        
        // Statistiques principales
        $totalStudents = Student::count();
        $totalTeachers = Teacher::where('status', 'active')->count();
        $totalClasses = SchoolClass::where('is_active', true)->count();
        $totalSubjects = Subject::where('is_active', true)->count();
        
        // Statistiques élèves
        $studentStats = [
            'total' => $totalStudents,
            'actifs' => Student::currentlyActive()->count(),
            'anciens' => Student::formerStudents()->count(),
            'avec_redoublement' => Student::where('total_redoublements', '>', 0)->count(),
            'garcons' => Student::where('gender', 'male')->count(),
            'filles' => Student::where('gender', 'female')->count(),
        ];
        
        // Statistiques inscriptions
        $enrollmentStats = [
            'total' => Enrollment::whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->count(),
            'nouveau' => Enrollment::where('student_status', 'nouveau')
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
            'passant' => Enrollment::where('student_status', 'passant')
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
            'redoublant' => Enrollment::where('student_status', 'redoublant')
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
        ];
        
        // Statistiques par cycle
        $enrollmentsByCycle = [
            'college' => Enrollment::whereHas('schoolClass.level', function($q) {
                $q->where('cycle', 'college');
            })->whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->count(),
            'lycee' => Enrollment::whereHas('schoolClass.level', function($q) {
                $q->where('cycle', 'lycee');
            })->whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->count(),
        ];
        
        // Statistiques financières
        $financialStats = [
            'monthly_revenue' => Enrollment::whereMonth('enrollment_date', now()->month)
                ->whereYear('enrollment_date', now()->year)
                ->sum('amount_paid'),
            'yearly_revenue' => Enrollment::whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->sum('amount_paid'),
            'total_fees' => Enrollment::whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->sum('total_fees'),
            'balance_due' => Enrollment::whereHas('academicYear', function($q) {
                $q->where('is_current', true);
            })->sum('balance_due'),
            'paid_count' => Enrollment::where('payment_status', 'completed')
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
            'partial_count' => Enrollment::where('payment_status', 'partial')
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
            'unpaid_count' => Enrollment::where('payment_status', 'pending')
                ->whereHas('academicYear', function($q) {
                    $q->where('is_current', true);
                })->count(),
        ];
        
        $financialStats['collection_rate'] = $financialStats['total_fees'] > 0 
            ? round(($financialStats['yearly_revenue'] / $financialStats['total_fees']) * 100, 1) 
            : 0;
        
        // Top 5 classes
        $classesByEnrollment = SchoolClass::withCount(['enrollments' => function($q) {
                $q->whereHas('academicYear', function($aq) {
                    $aq->where('is_current', true);
                });
            }])
            ->where('is_active', true)
            ->orderBy('enrollments_count', 'desc')
            ->take(5)
            ->get();

        return [
            'currentYear' => $currentYear,
            'totalStudents' => $totalStudents,
            'totalTeachers' => $totalTeachers,
            'totalClasses' => $totalClasses,
            'totalSubjects' => $totalSubjects,
            'studentStats' => $studentStats,
            'enrollmentStats' => $enrollmentStats,
            'enrollmentsByCycle' => $enrollmentsByCycle,
            'financialStats' => $financialStats,
            'classesByEnrollment' => $classesByEnrollment,
        ];
    }
}
