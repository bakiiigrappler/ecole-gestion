<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Afficher la liste des classes avec le nombre d'élèves
     */
    public function index(Request $request)
    {
        $academicYearId = $request->get('academic_year_id');
        
        // Récupérer l'année académique courante si non spécifiée
        if (!$academicYearId) {
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentAcademicYear ? $currentAcademicYear->id : null;
        }

        // Récupérer toutes les classes avec le nombre d'élèves
        $classes = SchoolClass::withCount(['students' => function($query) use ($academicYearId) {
            if ($academicYearId) {
                $query->where('enrollments.academic_year_id', $academicYearId)
                      ->where('enrollments.status', 'active');
            }
        }])
        ->with('level')
        ->orderBy('name')
        ->get();

        // Récupérer les années académiques pour le filtre
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $currentAcademicYear = AcademicYear::find($academicYearId);

        return view('attendances.index', compact('classes', 'academicYears', 'currentAcademicYear', 'academicYearId'));
    }

    /**
     * Afficher la page de gestion des présences pour une classe
     */
    public function manage(Request $request, SchoolClass $class)
    {
        $academicYearId = $request->get('academic_year_id');
        
        // Récupérer l'année académique courante si non spécifiée
        if (!$academicYearId) {
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentAcademicYear ? $currentAcademicYear->id : null;
        }

        $academicYear = AcademicYear::find($academicYearId);
        
        // Récupérer les étudiants de la classe
        $students = $class->students()
            ->wherePivot('academic_year_id', $academicYearId)
            ->wherePivot('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Date du jour ou date spécifiée
        $today = $request->get('date') ? Carbon::parse($request->get('date')) : Carbon::today();
        
        // Récupérer les présences du jour par créneaux horaires
        $todayAttendances = Attendance::where('class_id', $class->id)
            ->whereDate('attendance_date', $today)
            ->get()
            ->groupBy('student_id');
            
        // Debug: logger les données récupérées
        Log::info('Manage attendances for date: ' . $today->format('Y-m-d'), [
            'class_id' => $class->id,
            'attendances_count' => $todayAttendances->count(),
            'student_ids' => $todayAttendances->keys()->toArray()
        ]);

        // Récupérer les présences de la semaine courante
        $weekAttendances = Attendance::where('class_id', $class->id)
            ->whereBetween('attendance_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->groupBy(function($attendance) {
                return $attendance->attendance_date->format('Y-m-d');
            });

        // Statistiques de la semaine
        $weekStats = $this->getWeekStats($class->id, $academicYearId);

        return view('attendances.manage', compact(
            'class', 
            'academicYear', 
            'students', 
            'today', 
            'todayAttendances', 
            'weekAttendances',
            'weekStats',
            'academicYearId'
        ));
    }

    /**
     * Afficher la page de modification des présences pour une date spécifique
     */
    public function edit(Request $request, SchoolClass $class, $date)
    {
        $academicYearId = $request->get('academic_year_id');
        
        // Récupérer l'année académique courante si non spécifiée
        if (!$academicYearId) {
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentAcademicYear ? $currentAcademicYear->id : null;
        }

        $academicYear = AcademicYear::find($academicYearId);
        
        // Récupérer les étudiants de la classe
        $students = $class->students()
            ->wherePivot('academic_year_id', $academicYearId)
            ->wherePivot('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Date spécifiée pour la modification
        $editDate = Carbon::parse($date);
        
        // Récupérer les présences existantes pour cette date
        $todayAttendances = Attendance::where('class_id', $class->id)
            ->whereDate('attendance_date', $editDate)
            ->get();
            
        // Debug: logger les données récupérées
        Log::info('Edit attendances for date: ' . $editDate->format('Y-m-d'), [
            'class_id' => $class->id,
            'attendances_count' => $todayAttendances->count(),
            'attendances' => $todayAttendances->toArray()
        ]);
        
        // Si aucune donnée pour cette date, essayer de trouver des données récentes
        if ($todayAttendances->isEmpty()) {
            Log::info('No attendances found for date: ' . $editDate->format('Y-m-d') . ', searching for recent data');
            
            // Chercher les données les plus récentes pour cette classe
            $recentAttendances = Attendance::where('class_id', $class->id)
                ->orderBy('attendance_date', 'desc')
                ->limit(50)
                ->get();
                
            Log::info('Recent attendances found: ' . $recentAttendances->count());
            
            // Utiliser les données récentes pour le debug
            $todayAttendances = $recentAttendances;
        }
        
        $todayAttendances = $todayAttendances->groupBy('student_id');

        // Récupérer les présences de la semaine courante
        $weekAttendances = Attendance::where('class_id', $class->id)
            ->whereBetween('attendance_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->groupBy(function($attendance) {
                return $attendance->attendance_date->format('Y-m-d');
            });

        // Statistiques de la semaine
        $weekStats = $this->getWeekStats($class->id, $academicYearId);

        return view('attendances.edit', compact(
            'class', 
            'academicYear', 
            'students', 
            'editDate', 
            'todayAttendances', 
            'weekAttendances',
            'weekStats',
            'academicYearId'
        ));
    }

    /**
     * Afficher les détails des présences d'un jour spécifique
     */
    public function view(Request $request, SchoolClass $class, $date)
    {
        $academicYearId = $request->get('academic_year_id');
        
        // Récupérer l'année académique courante si non spécifiée
        if (!$academicYearId) {
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentAcademicYear ? $currentAcademicYear->id : null;
        }

        $academicYear = AcademicYear::find($academicYearId);
        
        // Récupérer les étudiants de la classe
        $students = $class->students()
            ->wherePivot('academic_year_id', $academicYearId)
            ->wherePivot('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Date spécifiée pour la consultation
        $viewDate = Carbon::parse($date);
        
        // Récupérer les présences existantes pour cette date
        $todayAttendances = Attendance::where('class_id', $class->id)
            ->whereDate('attendance_date', $viewDate)
            ->get()
            ->groupBy('student_id');

        // Récupérer les présences de la semaine courante
        $weekAttendances = Attendance::where('class_id', $class->id)
            ->whereBetween('attendance_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->groupBy(function($attendance) {
                return $attendance->attendance_date->format('Y-m-d');
            });

        // Calculer les statistiques de la semaine
        $weekStats = $this->getWeekStats($class->id, $academicYearId);

        return view('attendances.view', compact(
            'class', 
            'academicYear', 
            'students', 
            'viewDate', 
            'todayAttendances', 
            'weekAttendances',
            'weekStats',
            'academicYearId'
        ));
    }

    /**
     * Enregistrer les présences du jour
     */
    public function store(Request $request, SchoolClass $class)
    {
        $request->validate([
            'attendance_date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.time_slot' => 'required|string',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.arrival_time' => 'nullable|date_format:H:i',
            'attendances.*.reason' => 'nullable|string|max:500',
            'attendances.*.justified' => 'nullable|boolean'
        ]);

        $attendanceDate = Carbon::parse($request->attendance_date);
        
        DB::beginTransaction();
        
        try {
            // Grouper les présences par étudiant pour traiter la logique globale
            $attendancesByStudent = [];
            foreach ($request->attendances as $attendanceData) {
                $studentId = $attendanceData['student_id'];
                if (!isset($attendancesByStudent[$studentId])) {
                    $attendancesByStudent[$studentId] = [];
                }
                $attendancesByStudent[$studentId][] = $attendanceData;
            }

            foreach ($attendancesByStudent as $studentId => $studentAttendances) {
                // Analyser la présence globale de l'élève
                $hasPresent = false;
                $hasJustifiedAbsence = false;
                $hasUnjustifiedAbsence = false;
                $firstArrivalTime = null;
                $firstPresenceTime = null;
                $firstTimeSlot = null;
                
                // Vérifier s'il y a au moins une présence
                foreach ($studentAttendances as $attendance) {
                    if ($attendance['status'] === 'present') {
                        $hasPresent = true;
                        
                        // Trouver la première heure d'arrivée (priorité à 07:30)
                        if ($attendance['time_slot'] === '07:30' && $attendance['arrival_time']) {
                            $firstArrivalTime = $attendance['arrival_time'];
                            $firstTimeSlot = '07:30';
                        } elseif (!$firstArrivalTime && $attendance['arrival_time']) {
                            $firstArrivalTime = $attendance['arrival_time'];
                            $firstTimeSlot = $attendance['time_slot'];
                        }
                        
                        // Récupérer la première présence enregistrée (pour calculer le retard)
                        if (!$firstPresenceTime && $attendance['arrival_time']) {
                            $firstPresenceTime = $attendance['arrival_time'];
                        }
                    }
                    
                    // Vérifier les absences justifiées et non justifiées
                    if ($attendance['status'] === 'absent') {
                        if ($attendance['justified'] ?? false) {
                            $hasJustifiedAbsence = true;
                        } else {
                            $hasUnjustifiedAbsence = true;
                        }
                    }
                }
                
                // Traiter chaque créneau horaire
                foreach ($studentAttendances as $attendanceData) {
                    $finalStatus = $attendanceData['status'];
                    $finalArrivalTime = $attendanceData['arrival_time'] ?? null;
                    $finalReason = $attendanceData['reason'] ?? null;
                    $finalJustified = $attendanceData['justified'] ?? false;
                    
                    // Logique intelligente de présence
                    if ($hasPresent) {
                        // Si l'élève a été présent au moins une heure
                        if ($attendanceData['status'] === 'absent' && $finalJustified) {
                            // Annuler les absences justifiées
                            $finalStatus = 'present';
                            $finalReason = null;
                            $finalJustified = false;
                        } elseif ($attendanceData['status'] === 'absent' && !$finalJustified) {
                            // Garder les absences non justifiées
                            $finalStatus = 'absent';
                        }
                        
                        // Calculer le retard
                        if ($attendanceData['status'] === 'present') {
                            // Vérifier le retard à la première heure (07:30)
                            if ($attendanceData['time_slot'] === '07:30' && $finalArrivalTime) {
                                try {
                                    $arrivalTime = Carbon::createFromFormat('H:i', $finalArrivalTime);
                                    $startTime = Carbon::createFromFormat('H:i', '07:30');
                                    
                                    if ($arrivalTime->gt($startTime)) {
                                        $finalStatus = 'late';
                                    }
                                } catch (\Exception $e) {
                                    Log::warning("Erreur de calcul de retard pour l'étudiant {$studentId} à 07:30: " . $e->getMessage());
                                }
                            }
                            // Pour les autres créneaux, vérifier si c'est la première présence
                            elseif ($firstPresenceTime && $finalArrivalTime === $firstPresenceTime) {
                                try {
                                    $arrivalTime = Carbon::createFromFormat('H:i', $firstPresenceTime);
                                    $startTime = Carbon::createFromFormat('H:i', '07:30');
                                    
                                    if ($arrivalTime->gt($startTime)) {
                                        $finalStatus = 'late';
                                    }
                                } catch (\Exception $e) {
                                    Log::warning("Erreur de calcul de retard basé sur première présence pour l'étudiant {$studentId}: " . $e->getMessage());
                                }
                            }
                        }
                    }
                    
                    Attendance::updateOrCreate(
                        [
                            'student_id' => $attendanceData['student_id'],
                            'class_id' => $class->id,
                            'attendance_date' => $attendanceDate,
                            'time_slot' => $attendanceData['time_slot']
                        ],
                        [
                            'status' => $finalStatus,
                            'arrival_time' => $finalArrivalTime,
                            'reason' => $finalReason,
                            'justified' => $finalJustified
                        ]
                    );
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Présences enregistrées avec succès'
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement des présences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher les détails d'une journée de présence
     */
    public function show(Request $request, SchoolClass $class, $date)
    {
        $attendanceDate = Carbon::parse($date);
        $academicYearId = $request->get('academic_year_id');
        
        // Récupérer les étudiants de la classe
        $students = $class->students()
            ->wherePivot('academic_year_id', $academicYearId)
            ->wherePivot('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Récupérer les présences du jour
        $attendances = Attendance::where('class_id', $class->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->get();

        $academicYear = AcademicYear::find($academicYearId);

        // Si format JSON demandé, retourner les données en JSON
        if ($request->get('format') === 'json') {
            return response()->json([
                'success' => true,
                'students' => $students,
                'attendances' => $attendances,
                'class' => $class,
                'date' => $attendanceDate->format('Y-m-d'),
                'academicYear' => $academicYear
            ]);
        }

        return view('attendances.show', compact(
            'class', 
            'academicYear', 
            'students', 
            'attendanceDate', 
            'attendances',
            'academicYearId'
        ));
    }

    /**
     * Filtrer les présences par période
     */
    public function filter(Request $request, SchoolClass $class)
    {
        $academicYearId = $request->get('academic_year_id');
        $filterType = $request->get('filter_type', 'week');
        $customDate = $request->get('custom_date');
        
        $query = Attendance::where('class_id', $class->id);
        
        if ($academicYearId) {
            $academicYear = AcademicYear::find($academicYearId);
            if ($academicYear) {
                $query->whereBetween('attendance_date', [
                    $academicYear->start_date,
                    $academicYear->end_date
                ]);
            }
        }
        
        switch ($filterType) {
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;
            case 'month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;
            case 'custom':
                if ($customDate) {
                    $startDate = Carbon::parse($customDate)->startOfWeek();
                    $endDate = Carbon::parse($customDate)->endOfWeek();
                } else {
                    $startDate = Carbon::now()->startOfWeek();
                    $endDate = Carbon::now()->endOfWeek();
                }
                break;
            default:
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
        }
        
        $attendances = $query->whereBetween('attendance_date', [$startDate, $endDate])
            ->with('student')
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->groupBy(function($attendance) {
                return $attendance->attendance_date->format('Y-m-d');
            });

        return response()->json([
            'success' => true,
            'attendances' => $attendances,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ]);
    }

    /**
     * Obtenir les statistiques de la semaine
     */
    private function getWeekStats($classId, $academicYearId)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        // Récupérer toutes les présences de la semaine
        $attendances = Attendance::where('class_id', $classId)
            ->whereBetween('attendance_date', [$startOfWeek, $endOfWeek])
            ->get()
            ->groupBy(['attendance_date', 'student_id']);
        
        $stats = (object) [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0
        ];
        
        // Analyser chaque jour et chaque étudiant
        foreach ($attendances as $date => $students) {
            foreach ($students as $studentId => $studentAttendances) {
                $hasPresent = false;
                $hasLate = false;
                $hasJustifiedAbsence = false;
                $hasUnjustifiedAbsence = false;
                $firstArrivalTime = null;
                $firstPresenceTime = null;
                
                // Analyser les créneaux de l'étudiant pour ce jour
                foreach ($studentAttendances as $attendance) {
                    if ($attendance->status === 'present') {
                        $hasPresent = true;
                        
                        // Trouver la première heure d'arrivée (priorité à 07:30)
                        if ($attendance->time_slot === '07:30' && $attendance->arrival_time) {
                            $firstArrivalTime = $attendance->arrival_time;
                        } elseif (!$firstArrivalTime && $attendance->arrival_time) {
                            $firstArrivalTime = $attendance->arrival_time;
                        }
                        
                        // Récupérer la première présence enregistrée
                        if (!$firstPresenceTime && $attendance->arrival_time) {
                            $firstPresenceTime = $attendance->arrival_time;
                        }
                    }
                    
                    // Vérifier les absences justifiées et non justifiées
                    if ($attendance->status === 'absent') {
                        if ($attendance->justified) {
                            $hasJustifiedAbsence = true;
                        } else {
                            $hasUnjustifiedAbsence = true;
                        }
                    }
                }
                
                // Appliquer la logique intelligente
                if ($hasPresent) {
                    // Si l'élève a été présent au moins une heure
                    if ($hasJustifiedAbsence) {
                        // Annuler les absences justifiées
                        $hasJustifiedAbsence = false;
                    }
                    
                    // Calculer le retard
                    if ($firstArrivalTime) {
                        try {
                            $arrivalTime = Carbon::createFromFormat('H:i', $firstArrivalTime);
                            $startTime = Carbon::createFromFormat('H:i', '07:30');
                            
                            if ($arrivalTime->gt($startTime)) {
                                $hasLate = true;
                            }
                        } catch (\Exception $e) {
                            Log::warning("Erreur de calcul de retard pour l'étudiant {$studentId}: " . $e->getMessage());
                        }
                    } elseif ($firstPresenceTime) {
                        try {
                            $arrivalTime = Carbon::createFromFormat('H:i', $firstPresenceTime);
                            $startTime = Carbon::createFromFormat('H:i', '07:30');
                            
                            if ($arrivalTime->gt($startTime)) {
                                $hasLate = true;
                            }
                        } catch (\Exception $e) {
                            Log::warning("Erreur de calcul de retard basé sur première présence pour l'étudiant {$studentId}: " . $e->getMessage());
                        }
                    }
                }
                
                // Déterminer le statut final de l'étudiant pour ce jour
                if ($hasPresent) {
                    if ($hasLate) {
                        $stats->late++;
                    } else {
                        $stats->present++;
                    }
                } else {
                    if ($hasJustifiedAbsence) {
                        $stats->excused++;
                    } else {
                        $stats->absent++;
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Mettre à jour les présences d'une journée
     */
    public function update(Request $request, SchoolClass $class)
    {
        $request->validate([
            'attendance_date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.time_slot' => 'required|string',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.arrival_time' => 'nullable|date_format:H:i',
            'attendances.*.reason' => 'nullable|string|max:500',
            'attendances.*.justified' => 'nullable|boolean'
        ]);

        $attendanceDate = Carbon::parse($request->attendance_date);
        
        DB::beginTransaction();
        
        try {
            // Grouper les présences par étudiant pour traiter la logique globale
            $attendancesByStudent = [];
            foreach ($request->attendances as $attendanceData) {
                $studentId = $attendanceData['student_id'];
                if (!isset($attendancesByStudent[$studentId])) {
                    $attendancesByStudent[$studentId] = [];
                }
                $attendancesByStudent[$studentId][] = $attendanceData;
            }

            foreach ($attendancesByStudent as $studentId => $studentAttendances) {
                // Analyser la présence globale de l'élève
                $hasPresent = false;
                $hasJustifiedAbsence = false;
                $hasUnjustifiedAbsence = false;
                $firstArrivalTime = null;
                $firstPresenceTime = null;
                $firstTimeSlot = null;
                
                // Vérifier s'il y a au moins une présence
                foreach ($studentAttendances as $attendance) {
                    if ($attendance['status'] === 'present') {
                        $hasPresent = true;
                        
                        // Trouver la première heure d'arrivée (priorité à 07:30)
                        if ($attendance['time_slot'] === '07:30' && $attendance['arrival_time']) {
                            $firstArrivalTime = $attendance['arrival_time'];
                            $firstTimeSlot = '07:30';
                        } elseif (!$firstArrivalTime && $attendance['arrival_time']) {
                            $firstArrivalTime = $attendance['arrival_time'];
                            $firstTimeSlot = $attendance['time_slot'];
                        }
                        
                        // Récupérer la première présence enregistrée (pour calculer le retard)
                        if (!$firstPresenceTime && $attendance['arrival_time']) {
                            $firstPresenceTime = $attendance['arrival_time'];
                        }
                    }
                    
                    // Vérifier les absences justifiées et non justifiées
                    if ($attendance['status'] === 'absent') {
                        if ($attendance['justified'] ?? false) {
                            $hasJustifiedAbsence = true;
                        } else {
                            $hasUnjustifiedAbsence = true;
                        }
                    }
                }
                
                // Traiter chaque créneau horaire
                foreach ($studentAttendances as $attendanceData) {
                    $finalStatus = $attendanceData['status'];
                    $finalArrivalTime = $attendanceData['arrival_time'] ?? null;
                    $finalReason = $attendanceData['reason'] ?? null;
                    $finalJustified = $attendanceData['justified'] ?? false;
                    
                    // Logique intelligente de présence
                    if ($hasPresent) {
                        // Si l'élève a été présent au moins une heure
                        if ($attendanceData['status'] === 'absent' && $finalJustified) {
                            // Annuler les absences justifiées
                            $finalStatus = 'present';
                            $finalReason = null;
                            $finalJustified = false;
                        } elseif ($attendanceData['status'] === 'absent' && !$finalJustified) {
                            // Garder les absences non justifiées
                            $finalStatus = 'absent';
                        }
                        
                        // Calculer le retard
                        if ($attendanceData['status'] === 'present') {
                            // Vérifier le retard à la première heure (07:30)
                            if ($attendanceData['time_slot'] === '07:30' && $finalArrivalTime) {
                                try {
                                    $arrivalTime = Carbon::createFromFormat('H:i', $finalArrivalTime);
                                    $startTime = Carbon::createFromFormat('H:i', '07:30');
                                    
                                    if ($arrivalTime->gt($startTime)) {
                                        $finalStatus = 'late';
                                    }
                                } catch (\Exception $e) {
                                    Log::warning("Erreur de calcul de retard pour l'étudiant {$studentId} à 07:30: " . $e->getMessage());
                                }
                            }
                            // Pour les autres créneaux, vérifier si c'est la première présence
                            elseif ($firstPresenceTime && $finalArrivalTime === $firstPresenceTime) {
                                try {
                                    $arrivalTime = Carbon::createFromFormat('H:i', $firstPresenceTime);
                                    $startTime = Carbon::createFromFormat('H:i', '07:30');
                                    
                                    if ($arrivalTime->gt($startTime)) {
                                        $finalStatus = 'late';
                                    }
                                } catch (\Exception $e) {
                                    Log::warning("Erreur de calcul de retard basé sur première présence pour l'étudiant {$studentId}: " . $e->getMessage());
                                }
                            }
                        }
                    }
                    
                    Attendance::updateOrCreate(
                        [
                            'student_id' => $attendanceData['student_id'],
                            'class_id' => $class->id,
                            'attendance_date' => $attendanceDate,
                            'time_slot' => $attendanceData['time_slot']
                        ],
                        [
                            'status' => $finalStatus,
                            'arrival_time' => $finalArrivalTime,
                            'reason' => $finalReason,
                            'justified' => $finalJustified
                        ]
                    );
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Présences mises à jour avec succès'
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des présences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer les présences d'une journée
     */
    public function delete(Request $request, SchoolClass $class)
    {
        $request->validate([
            'attendance_date' => 'required|date'
        ]);

        $attendanceDate = Carbon::parse($request->attendance_date);
        
        try {
            // Supprimer toutes les présences de la journée pour cette classe
            $deletedCount = Attendance::where('class_id', $class->id)
                ->whereDate('attendance_date', $attendanceDate)
                ->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Présences supprimées avec succès ({$deletedCount} enregistrements supprimés)"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression des présences: ' . $e->getMessage()
            ], 500);
        }
    }
}