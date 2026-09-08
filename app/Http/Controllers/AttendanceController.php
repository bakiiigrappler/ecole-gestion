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

        // L'appel se fait un jour donne : c'est la date, et non la classe,
        // qui commande la page. Elle se limite a l'annee scolaire consultee.
        $annee = AcademicYear::find($academicYearId);
        $jour = $request->filled('date')
            ? Carbon::parse($request->get('date'))->startOfDay()
            : Carbon::today();

        if ($annee) {
            $jour = $jour->max(Carbon::parse($annee->start_date))->min(Carbon::parse($annee->end_date));
        }

        // Récupérer toutes les classes avec le nombre d'élèves
        $classes = SchoolClass::withCount(['students' => function($query) use ($academicYearId) {
            if ($academicYearId) {
                $query->where('enrollments.academic_year_id', $academicYearId)
                      ->where('enrollments.status', 'active');
            }
        }])
        ->with('level')
        // Un enseignant ne fait l'appel que dans ses classes.
        ->when(\App\Support\PerimetreEnseignant::estEnseignant(), fn ($q) => $q
            ->whereIn('id', \App\Support\PerimetreEnseignant::classes() ?: [0]))
        ->orderBy('name')
        ->get();

        // Pointages du jour, une ligne par classe : sans cela, rien ne
        // distinguait une classe deja appelee d'une classe oubliee.
        $pointages = Attendance::whereDate('attendance_date', $jour)
            ->selectRaw('class_id, status, count(distinct student_id) as effectif')
            ->groupBy('class_id', 'status')
            ->get()
            ->groupBy('class_id')
            ->map(function ($lignes) {
                $par = $lignes->pluck('effectif', 'status');

                return [
                    'present' => (int) ($par['present'] ?? 0),
                    'absent' => (int) ($par['absent'] ?? 0),
                    'late' => (int) ($par['late'] ?? 0),
                    'excused' => (int) ($par['excused'] ?? 0),
                    'pointes' => (int) $par->sum(),
                ];
            });

        // Qui avait cours ce jour-là : l'administration doit savoir à qui
        // s'adresser quand l'appel n'a pas été fait.
        $enseignantsDuJour = \App\Models\Schedule::with('teacher:id,first_name,last_name')
            ->where('day_of_week', $jour->dayOfWeekIso)
            ->where('type', 'course')
            ->whereNotNull('teacher_id')
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
            ->orderBy('start_time')
            ->get()
            ->groupBy('class_id')
            ->map(fn ($lot) => $lot->pluck('teacher')
                ->filter()
                ->unique('id')
                ->map(fn ($e) => $e->first_name.' '.$e->last_name)
                ->values()
                ->all());

        $bilanDuJour = [
            'classes_pointees' => $pointages->count(),
            'present' => $pointages->sum('present'),
            'absent' => $pointages->sum('absent'),
            'late' => $pointages->sum('late'),
            'excused' => $pointages->sum('excused'),
            'pointes' => $pointages->sum('pointes'),
        ];

        // Récupérer les années académiques pour le filtre
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $currentAcademicYear = AcademicYear::find($academicYearId);

        return view('attendances.index', compact(
            'classes',
            'academicYears',
            'currentAcademicYear',
            'academicYearId',
            'jour',
            'pointages',
            'bilanDuJour',
            'enseignantsDuJour'
        ));
    }

    /**
     * Afficher la page de gestion des présences pour une classe
     */
    public function manage(Request $request, SchoolClass $class)
    {
        $this->verifierLePerimetre($class, $request->get('date'));

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
        $this->verifierLePerimetre($class, $request->input('attendance_date'));

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
                    if (($attendance['status'] ?? null) === 'present') {
                        $hasPresent = true;
                        
                        // Trouver la première heure d'arrivée (priorité à 07:30)
                        if (($attendance['time_slot'] ?? null) === '07:30' && ($attendance['arrival_time'] ?? null)) {
                            $firstArrivalTime = ($attendance['arrival_time'] ?? null);
                            $firstTimeSlot = '07:30';
                        } elseif (!$firstArrivalTime && ($attendance['arrival_time'] ?? null)) {
                            $firstArrivalTime = ($attendance['arrival_time'] ?? null);
                            $firstTimeSlot = $attendance['time_slot'] ?? null;
                        }
                        
                        // Récupérer la première présence enregistrée (pour calculer le retard)
                        if (!$firstPresenceTime && ($attendance['arrival_time'] ?? null)) {
                            $firstPresenceTime = ($attendance['arrival_time'] ?? null);
                        }
                    }
                    
                    // Vérifier les absences justifiées et non justifiées
                    if (($attendance['status'] ?? null) === 'absent') {
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
                            if (($attendanceData['time_slot'] ?? null) === '07:30' && $finalArrivalTime) {
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
            
            // La feuille d’appel est un formulaire classique : lui renvoyer du
            // JSON affichait un bloc de code a la place de la page.
            if (! $request->expectsJson()) {
                return redirect()->route('attendances.manage', ['class' => $class->id, 'date' => $attendanceDate->toDateString()])
                    ->with('success', 'Appel enregistré.');
            }

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
                    if (($attendance['status'] ?? null) === 'present') {
                        $hasPresent = true;
                        
                        // Trouver la première heure d'arrivée (priorité à 07:30)
                        if (($attendance['time_slot'] ?? null) === '07:30' && ($attendance['arrival_time'] ?? null)) {
                            $firstArrivalTime = ($attendance['arrival_time'] ?? null);
                            $firstTimeSlot = '07:30';
                        } elseif (!$firstArrivalTime && ($attendance['arrival_time'] ?? null)) {
                            $firstArrivalTime = ($attendance['arrival_time'] ?? null);
                            $firstTimeSlot = $attendance['time_slot'] ?? null;
                        }
                        
                        // Récupérer la première présence enregistrée (pour calculer le retard)
                        if (!$firstPresenceTime && ($attendance['arrival_time'] ?? null)) {
                            $firstPresenceTime = ($attendance['arrival_time'] ?? null);
                        }
                    }
                    
                    // Vérifier les absences justifiées et non justifiées
                    if (($attendance['status'] ?? null) === 'absent') {
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
                            if (($attendanceData['time_slot'] ?? null) === '07:30' && $finalArrivalTime) {
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

    /**
     * Afficher les rapports de présences pour une classe
     */
    public function reports(Request $request, SchoolClass $class)
    {
        $academicYearId = $request->get('academic_year_id')
            ?? AcademicYear::where('is_current', true)->value('id');

        $academicYear = AcademicYear::find($academicYearId);

        $students = $class->students()
            ->wherePivot('academic_year_id', $academicYearId)
            ->wherePivot('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Une seule periode gouverne toute la page : melanger semaine, mois et
        // annee dans trois blocs distincts rendait le rapport illisible.
        $periode = in_array($request->get('periode'), ['semaine', 'mois', 'trimestre', 'annee'], true)
            ? $request->get('periode')
            : 'semaine';

        [$debut, $fin, $libellePeriode] = $this->bornesDeLaPeriode($periode, $academicYear);

        $pointages = Attendance::where('class_id', $class->id)
            ->whereBetween('attendance_date', [$debut->toDateString(), $fin->toDateString()])
            ->get(['student_id', 'attendance_date', 'status', 'reason']);

        $compter = fn ($lot) => [
            'present' => $lot->where('status', 'present')->count(),
            'absent' => $lot->where('status', 'absent')->count(),
            'late' => $lot->where('status', 'late')->count(),
            'excused' => $lot->where('status', 'excused')->count(),
            'total' => $lot->count(),
        ];

        $bilan = $compter($pointages);
        $bilan['taux'] = $bilan['total'] > 0 ? round($bilan['present'] / $bilan['total'] * 100) : null;

        // Jour par jour : c'est ce qui permet de voir d'un coup d'oeil quel
        // jour a decroche, et d'ouvrir directement la feuille d'appel.
        $parJour = $pointages
            ->groupBy(fn ($p) => Carbon::parse($p->attendance_date)->toDateString())
            ->map(function ($lot) use ($compter) {
                $chiffres = $compter($lot);
                $chiffres['taux'] = $chiffres['total'] > 0
                    ? round($chiffres['present'] / $chiffres['total'] * 100)
                    : null;

                return $chiffres;
            })
            ->sortKeys();

        $parEleve = $pointages->groupBy('student_id');

        $assiduite = $students->map(function ($eleve) use ($parEleve, $compter) {
            $lot = $parEleve[$eleve->id] ?? collect();
            $chiffres = $compter($lot);

            $chiffres['eleve'] = $eleve;
            $chiffres['taux'] = $chiffres['total'] > 0
                ? round($chiffres['present'] / $chiffres['total'] * 100)
                : null;
            $chiffres['motifs'] = $lot->whereIn('status', ['absent', 'excused'])
                ->pluck('reason')
                ->filter()
                ->countBy()
                ->sortDesc()
                ->keys()
                ->take(2)
                ->all();

            return $chiffres;
        })
        ->sortBy(fn ($l) => $l['taux'] ?? 101)
        ->values();

        // Ce que le rapport doit faire remonter en premier : les eleves dont
        // l'assiduite decroche, plutot qu'un tableau a lire en entier.
        $aSurveiller = $assiduite
            ->filter(fn ($l) => $l['taux'] !== null && $l['taux'] < 90)
            ->take(6)
            ->values();

        return view('attendances.reports', compact(
            'class',
            'academicYear',
            'academicYearId',
            'students',
            'periode',
            'libellePeriode',
            'debut',
            'fin',
            'bilan',
            'parJour',
            'assiduite',
            'aSurveiller'
        ));
    }

    /**
     * Un enseignant ne pointe que ses classes, et seulement les jours où il
     * y a cours. Sans cette vérification, l'URL suffisait à faire l'appel
     * d'une classe qu'il ne voit jamais.
     */
    private function verifierLePerimetre(SchoolClass $class, $date = null): void
    {
        if (! \App\Support\PerimetreEnseignant::estEnseignant()) {
            return;
        }

        abort_unless(
            in_array($class->id, \App\Support\PerimetreEnseignant::classes(), true),
            403,
            'Vous n’intervenez pas dans cette classe.'
        );

        $jour = $date ? Carbon::parse($date) : Carbon::today();

        abort_unless(
            \App\Support\PerimetreEnseignant::aCoursCeJour($class->id, $jour),
            403,
            'Vous n’avez pas cours dans cette classe le '.$jour->locale('fr')->isoFormat('dddd').'.'
        );
    }

    /**
     * Bornes de la periode demandee, jamais hors de l'annee scolaire.
     */
    private function bornesDeLaPeriode(string $periode, ?AcademicYear $annee): array
    {
        $aujourdhui = Carbon::today();

        [$debut, $fin, $libelle] = match ($periode) {
            'mois' => [
                $aujourdhui->copy()->startOfMonth(),
                $aujourdhui->copy()->endOfMonth(),
                'Ce mois-ci',
            ],
            'trimestre' => [...$this->bornesDuTrimestre($annee, $aujourdhui), 'Ce trimestre'],
            'annee' => [
                $annee ? Carbon::parse($annee->start_date) : $aujourdhui->copy()->startOfYear(),
                $annee ? Carbon::parse($annee->end_date) : $aujourdhui->copy()->endOfYear(),
                'Cette année',
            ],
            default => [
                $aujourdhui->copy()->startOfWeek(),
                $aujourdhui->copy()->endOfWeek(),
                'Cette semaine',
            ],
        };

        if ($annee) {
            $debut = $debut->max(Carbon::parse($annee->start_date));
            $fin = $fin->min(Carbon::parse($annee->end_date));
        }

        return [$debut->startOfDay(), $fin->endOfDay(), $libelle];
    }

    /**
     * Trimestre en cours, avec le meme decoupage que les bulletins :
     * septembre-decembre, janvier-mars, avril-juin.
     */
    private function bornesDuTrimestre(?AcademicYear $annee, Carbon $jour): array
    {
        $an = $annee ? Carbon::parse($annee->start_date)->year : $jour->year;

        return match (true) {
            $jour->month >= 9 => [Carbon::create($an, 9, 1), Carbon::create($an, 12, 31)],
            $jour->month <= 3 => [Carbon::create($an + 1, 1, 1), Carbon::create($an + 1, 3, 31)],
            default => [Carbon::create($an + 1, 4, 1), Carbon::create($an + 1, 6, 30)],
        };
    }
}