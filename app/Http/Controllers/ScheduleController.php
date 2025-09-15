<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Afficher la liste des emplois du temps par classe
     */
    public function index(Request $request)
    {
        // Récupérer l'année académique courante ou celle sélectionnée
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear?->id;
        }

        // Récupérer TOUS les emplois du temps pour cette année académique
        $allSchedules = Schedule::where('academic_year_id', $academicYearId)
            ->with(['schoolClass', 'subject', 'teacher'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Récupérer les classes qui ont des emplois du temps
        $classesWithSchedules = SchoolClass::active()
            ->with(['level'])
            ->whereHas('schedules', function($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })
            ->orderBy('name')
            ->get();

        // Charger les emplois du temps pour chaque classe
        foreach ($classesWithSchedules as $class) {
            $class->schedules = Schedule::where('class_id', $class->id)
                ->where('academic_year_id', $academicYearId)
                ->get();
        }

        // Récupérer les emplois du temps orphelins (sans classe OU avec classe inexistante)
        $orphanSchedules = $allSchedules->filter(function($schedule) {
            return !$schedule->class_id || !SchoolClass::find($schedule->class_id);
        });

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $currentAcademicYear = AcademicYear::find($academicYearId);

        return view('schedules.index', compact('allSchedules', 'classesWithSchedules', 'orphanSchedules', 'academicYears', 'currentAcademicYear'));
    }

    /**
     * Afficher le formulaire de création d'emploi du temps (sélection de classe)
     */
    public function create()
    {
        $classes = SchoolClass::active()->with('level')->orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();

        return view('schedules.create', compact('classes', 'academicYears', 'currentAcademicYear'));
    }

    /**
     * Afficher le formulaire de constitution d'emploi du temps pour une classe
     */
    public function build(Request $request)
    {
        $classId = $request->get('class_id');
        $academicYearId = $request->get('academic_year_id');

        // Rediriger vers la sélection de classe si pas de classe
        if (!$classId) {
            return redirect()->route('schedules.create')->with('error', 'Veuillez d\'abord sélectionner une classe.');
        }

        // Si pas d'année académique, utiliser l'année en cours
        if (!$academicYearId) {
            $academicYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $academicYear->id;
        } else {
            $academicYear = AcademicYear::findOrFail($academicYearId);
        }

        $class = SchoolClass::with('level')->findOrFail($classId);
        
        // Récupérer les matières selon le cycle
        $subjects = [];
        if ($class->level) {
            $cycle = $class->level->cycle;
            
            if ($cycle === 'preprimaire' || $cycle === 'primaire') {
                // Pour le préprimaire et primaire, récupérer les matières du cycle
                $subjects = Subject::active()
                    ->where('cycle', $cycle)
                    ->orderBy('name')
                    ->get();
            } else {
                // Pour le secondaire (collège et lycée), récupérer les matières du niveau
                $subjects = Subject::active()
                    ->where('level_id', $class->level_id)
                    ->orderBy('name')
                    ->get();
                
                // Si c'est le lycée, filtrer par série si spécifiée
                if ($cycle === 'lycee' && $class->series) {
                    $subjects = $subjects->filter(function($subject) use ($class) {
                        return $subject->isApplicableToSeries($class->series);
                    });
                }
            }
        }
        
        // Récupérer tous les enseignants actifs
        $teachers = Teacher::active()->orderBy('first_name')->get();
        
        // Récupérer les enseignants par matière pour l'utilisation côté client
        $teachersBySubject = [];
        foreach ($subjects as $subject) {
            $teachersBySubject[$subject->id] = $subject->teachers()
                ->active()
                ->get(['teachers.id', 'teachers.first_name', 'teachers.last_name'])
                ->map(function($teacher) {
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->first_name . ' ' . $teacher->last_name
                    ];
                });
        }

        // Récupérer l'emploi du temps existant s'il y en a un
        $existingSchedules = Schedule::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Créneaux horaires par défaut selon le cycle
        $defaultTimeSlots = $this->getDefaultTimeSlots($class->level ? $class->level->cycle : 'primaire');

        // Jours de la semaine
        $days = [
            1 => 'Lundi',
            2 => 'Mardi', 
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi'
        ];

        return view('schedules.build', compact(
            'class', 'academicYear', 'subjects', 'teachers', 'teachersBySubject',
            'existingSchedules', 'defaultTimeSlots', 'days'
        ));
    }

    /**
     * Obtenir les créneaux horaires par défaut selon le cycle
     */
    private function getDefaultTimeSlots($cycle)
    {
        switch ($cycle) {
            case 'preprimaire':
                return [
                    ['start' => '08:00', 'end' => '08:30'],
                    ['start' => '08:30', 'end' => '09:00'],
                    ['start' => '09:00', 'end' => '09:30'],
                    ['start' => '09:30', 'end' => '10:00'],
                    ['start' => '10:00', 'end' => '10:15'], // Récréation
                    ['start' => '10:15', 'end' => '10:45'],
                    ['start' => '10:45', 'end' => '11:15'],
                    ['start' => '11:15', 'end' => '11:45'],
                    ['start' => '11:45', 'end' => '12:00'], // Pause
                ];
            
            case 'primaire':
                return [
                    ['start' => '08:00', 'end' => '09:00'],
                    ['start' => '09:00', 'end' => '10:00'],
                    ['start' => '10:00', 'end' => '10:15'], // Récréation
                    ['start' => '10:15', 'end' => '11:15'],
                    ['start' => '11:15', 'end' => '12:15'],
                    ['start' => '12:15', 'end' => '13:15'], // Pause déjeuner
                    ['start' => '13:15', 'end' => '14:15'],
                    ['start' => '14:15', 'end' => '15:15'],
                    ['start' => '15:15', 'end' => '15:30'], // Récréation
                    ['start' => '15:30', 'end' => '16:30'],
                ];
            
            default: // Collège et Lycée
                return [
                    ['start' => '08:00', 'end' => '09:00'],
                    ['start' => '09:00', 'end' => '10:00'],
                    ['start' => '10:00', 'end' => '10:15'], // Récréation
                    ['start' => '10:15', 'end' => '11:15'],
                    ['start' => '11:15', 'end' => '12:15'],
                    ['start' => '12:15', 'end' => '13:15'], // Pause déjeuner
                    ['start' => '13:15', 'end' => '14:15'],
                    ['start' => '14:15', 'end' => '15:15'],
                    ['start' => '15:15', 'end' => '15:30'], // Récréation
                    ['start' => '15:30', 'end' => '16:30'],
                    ['start' => '16:30', 'end' => '17:30'],
                ];
        }
    }

    /**
     * Enregistrer l'emploi du temps d'une classe
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'cycle' => 'required|string',
            'schedule' => 'required|array',
        ]);

        DB::beginTransaction();
        
        try {
            // Supprimer l'emploi du temps existant pour cette classe et cette année
            Schedule::where('class_id', $validated['class_id'])
                   ->where('academic_year_id', $validated['academic_year_id'])
                   ->delete();

            $createdSchedules = 0;

            // Définir les créneaux horaires avec format standard
            $timeSlots = [
                ['start' => '07:30:00', 'end' => '08:30:00'],
                ['start' => '08:30:00', 'end' => '09:30:00'],
                ['start' => '09:30:00', 'end' => '10:30:00'],
                ['start' => '10:45:00', 'end' => '11:45:00'],
                ['start' => '11:45:00', 'end' => '12:45:00'],
                ['start' => '14:00:00', 'end' => '15:00:00'],
                ['start' => '15:00:00', 'end' => '16:00:00'],
                ['start' => '16:15:00', 'end' => '17:15:00']
            ];

            // Traiter les données de l'emploi du temps
            // Le format peut être soit un tableau indexé par créneaux, soit un tableau plat
            $scheduleItems = $validated['schedule'];
            
            // Vérifier si c'est un tableau plat (nouveau format) ou indexé par créneaux (ancien format)
            $isFlatArray = false;
            if (!empty($scheduleItems) && isset($scheduleItems[0]) && is_array($scheduleItems[0])) {
                // Vérifier si le premier élément a les clés attendues pour un élément plat
                $firstItem = $scheduleItems[0];
                if (isset($firstItem['day']) && isset($firstItem['start_time']) && isset($firstItem['end_time'])) {
                    $isFlatArray = true;
                }
            }
            
            if ($isFlatArray) {
                // Nouveau format : tableau plat d'éléments de planning
                foreach ($scheduleItems as $scheduleData) {
                    if (empty($scheduleData)) continue;
                    
                    $dayOfWeek = (int) $scheduleData['day'];
                    $startTime = Carbon::createFromFormat('H:i:s', $scheduleData['start_time']);
                    $endTime = Carbon::createFromFormat('H:i:s', $scheduleData['end_time']);
                    
                    if ($validated['cycle'] === 'college' || $validated['cycle'] === 'lycee') {
                        // Pour le collège/lycée avec données réelles
                        if (!empty($scheduleData['subject_id'])) {
                            $scheduleRecord = [
                                'class_id' => $validated['class_id'],
                                'academic_year_id' => $validated['academic_year_id'],
                                'day_of_week' => $dayOfWeek,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'subject_id' => $scheduleData['subject_id'],
                                'subject_name' => $scheduleData['subject_name'] ?? null,
                                'teacher_id' => $scheduleData['teacher_id'] ?? null,
                                'type' => $scheduleData['type'] ?? 'course',
                                'is_active' => true,
                            ];

                            Schedule::create($scheduleRecord);
                            $createdSchedules++;
                        }
                    } else {
                        // Pour le préprimaire/primaire avec données de base
                        if (!empty($scheduleData['subject_id']) && !empty($scheduleData['teacher_id'])) {
                            $scheduleRecord = [
                                'class_id' => $validated['class_id'],
                                'academic_year_id' => $validated['academic_year_id'],
                                'day_of_week' => $dayOfWeek,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'subject_name' => $scheduleData['subject_id'], // Dans l'ancien format, c'était le nom
                                'teacher_name' => $scheduleData['teacher_id'], // Dans l'ancien format, c'était le nom
                                'type' => $scheduleData['type'] ?? 'course',
                                'is_active' => true,
                            ];

                            Schedule::create($scheduleRecord);
                            $createdSchedules++;
                        }
                    }
                }
            } else {
                // Ancien format : tableau indexé par créneaux horaires
                foreach ($scheduleItems as $timeIndex => $days) {
                    $timeSlot = $timeSlots[$timeIndex] ?? ['start' => '07:30:00', 'end' => '08:30:00'];
                    $startTime = Carbon::createFromFormat('H:i:s', $timeSlot['start']);
                    $endTime = Carbon::createFromFormat('H:i:s', $timeSlot['end']);

                    // Traiter chaque jour
                    foreach ($days as $dayIndex => $scheduleData) {
                        if (empty($scheduleData)) continue;

                        // Déterminer le jour de la semaine (0 = Lundi, 1 = Mardi, etc.)
                        $dayOfWeek = $dayIndex + 1; // Convertir en 1-6 pour la base

                        if ($validated['cycle'] === 'college' || $validated['cycle'] === 'lycee') {
                            // Pour le collège/lycée avec données réelles
                            if (!empty($scheduleData['subject_id'])) {
                                $scheduleRecord = [
                                    'class_id' => $validated['class_id'],
                                    'academic_year_id' => $validated['academic_year_id'],
                                    'day_of_week' => $dayOfWeek,
                                    'start_time' => $startTime,
                                    'end_time' => $endTime,
                                    'subject_id' => $scheduleData['subject_id'],
                                    'subject_name' => $scheduleData['subject_name'],
                                    'teacher_id' => $scheduleData['teacher_id'] ?? null,
                                    'type' => 'course',
                                    'is_active' => true,
                                ];

                                Schedule::create($scheduleRecord);
                                $createdSchedules++;
                            }
                        } else {
                            // Pour le préprimaire/primaire avec données de base
                            if (!empty($scheduleData['subject']) && !empty($scheduleData['teacher'])) {
                                $scheduleRecord = [
                                    'class_id' => $validated['class_id'],
                                    'academic_year_id' => $validated['academic_year_id'],
                                    'day_of_week' => $dayOfWeek,
                                    'start_time' => $startTime,
                                    'end_time' => $endTime,
                                    'subject_name' => $scheduleData['subject'],
                                    'teacher_name' => $scheduleData['teacher'],
                                    'type' => 'course',
                                    'is_active' => true,
                                ];

                                Schedule::create($scheduleRecord);
                                $createdSchedules++;
                            }
                        }
                    }
                }
            }

            DB::commit();

            $message = "Emploi du temps enregistré avec succès! {$createdSchedules} créneaux créés.";
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'schedules_created' => $createdSchedules
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la sauvegarde de l\'emploi du temps: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Afficher les détails de l'emploi du temps d'une classe
     */
    public function show(Schedule $schedule, Request $request)
    {
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $academicYearId = $schedule->academic_year_id;
        }

        // Récupérer la classe à partir de l'emploi du temps
        $class = $schedule->schoolClass;
        if (!$class) {
            abort(404, 'Classe non trouvée pour cet emploi du temps');
        }
        $class->load('level');
        $academicYear = AcademicYear::find($academicYearId);

        $schedules = Schedule::with(['subject', 'teacher'])
            ->where('class_id', $class->id)
            ->where('academic_year_id', $academicYearId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Organiser les horaires par jour
        $schedulesByDay = $schedules->groupBy('day_of_week');
        
        $days = [
            1 => 'Lundi',
            2 => 'Mardi', 
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi'
        ];

        return view('schedules.show', compact('class', 'academicYear', 'schedules', 'schedulesByDay', 'days'));
    }

    /**
     * Afficher le formulaire d'édition d'emploi du temps
     */
    public function edit(SchoolClass $class, Request $request)
    {
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear?->id;
        }

        return redirect()->route('schedules.build', [
            'class_id' => $class->id,
            'academic_year_id' => $academicYearId
        ]);
    }

    /**
     * Supprimer l'emploi du temps d'une classe
     */
    public function destroy(SchoolClass $class, Request $request)
    {
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear?->id;
        }

        $deletedCount = Schedule::where('class_id', $class->id)
            ->where('academic_year_id', $academicYearId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Emploi du temps supprimé avec succès! {$deletedCount} créneaux supprimés."
        ]);
    }

    /**
     * API: Obtenir les matières par niveau
     */
    public function getSubjectsByLevel(Request $request)
    {
        $levelId = $request->get('level_id');
        
        $subjects = Subject::active()
            ->where('level_id', $levelId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($subjects);
    }

    /**
     * API: Récupérer les classes par cycle
     */
    public function getClassesByCycle(Request $request)
    {
        try {
            $cycle = $request->get('cycle');
            
            if (!$cycle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cycle non spécifié',
                    'classes' => []
                ]);
            }

            $classes = SchoolClass::active()
                ->with('level')
                ->whereHas('level', function($query) use ($cycle) {
                    $query->where('cycle', $cycle);
                })
                ->orderBy('name')
                ->get()
                ->map(function($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'level_id' => $class->level_id,
                        'level_name' => $class->level ? $class->level->name : 'Niveau non défini',
                        'cycle' => $class->level ? $class->level->cycle : 'Non défini',
                        'series' => $class->series ?? null
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Classes chargées avec succès',
                'classes' => $classes
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur dans getClassesByCycle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des classes',
                'classes' => []
            ], 500);
        }
    }

    /**
     * Vérifier l'existence d'un emploi du temps
     */
    public function checkExisting(Request $request)
    {
        try {
            $classId = $request->get('class_id');
            $academicYearId = $request->get('academic_year_id');

            if (!$classId || !$academicYearId) {
                return response()->json(['exists' => false]);
            }

            $exists = Schedule::where('class_id', $classId)
                ->where('academic_year_id', $academicYearId)
                ->exists();

            return response()->json(['exists' => $exists]);
        } catch (\Exception $e) {
            Log::error('Erreur dans checkExisting: ' . $e->getMessage());
            return response()->json(['exists' => false]);
        }
    }

    /**
     * API: Vérifier les conflits d'horaires
     */
    public function checkConflicts(Request $request)
    {
        $conflicts = Schedule::validateTimeSlot(
            $request->class_id,
            $request->teacher_id,
            $request->academic_year_id,
            $request->day_of_week,
            $request->start_time,
            $request->end_time,
            $request->exclude_id
        );

        return response()->json($conflicts);
    }

    /**
     * Afficher la version imprimable de l'emploi du temps d'une classe
     */
    public function print(SchoolClass $class, Request $request)
    {
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear?->id;
        }

        $class->load('level');
        $academicYear = AcademicYear::find($academicYearId);

        $schedules = Schedule::with(['subject', 'teacher'])
            ->where('class_id', $class->id)
            ->where('academic_year_id', $academicYearId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Organiser les horaires par jour
        $schedulesByDay = $schedules->groupBy('day_of_week');
        
        $days = [
            1 => 'Lundi',
            2 => 'Mardi', 
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi'
        ];

        return view('schedules.print', compact('class', 'academicYear', 'schedulesByDay', 'days'));
    }

    /**
     * API: Récupérer les matières et enseignants d'une classe
     */
    public function getClassSubjectsAndTeachers(Request $request)
    {
        try {
            $classId = $request->get('class_id');
            
            if (!$classId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de classe requis'
                ], 400);
            }

            $class = SchoolClass::with('level')->findOrFail($classId);
            
            // Récupérer tous les enseignants actifs avec leurs matières
            $teachers = Teacher::active()
                ->with('subjects')  // ✅ Charger les relations matière
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name']);

            Log::info('Enseignants actifs trouvés: ' . $teachers->count());

            // Essayer de récupérer les enseignants spécifiquement assignés à cette classe
            try {
                $classTeachers = Teacher::active()
                    ->whereHas('classes', function($query) use ($classId) {
                        $query->where('class_id', $classId);
                    })
                    ->with('subjects')  // ✅ Charger les relations matière
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get(['id', 'first_name', 'last_name']);

                Log::info('Enseignants assignés à la classe: ' . $classTeachers->count());

                // Si des enseignants sont assignés à la classe, les utiliser en priorité
                // MAIS garder les relations matière !
                if ($classTeachers->isNotEmpty()) {
                    $teachers = $classTeachers;
                    Log::info('Utilisation des enseignants assignés à la classe avec leurs matières');
                } else {
                    Log::info('Utilisation de tous les enseignants actifs avec leurs matières');
                }
            } catch (\Exception $e) {
                Log::warning('Erreur lors de la récupération des enseignants de classe: ' . $e->getMessage());
                Log::info('Utilisation de tous les enseignants actifs avec leurs matières');
            }

            // Récupérer les matières du cycle depuis la base de données
            $subjects = collect();
            try {
                if ($class->level) {
                    $cycle = $class->level->cycle;
                    
                    // Récupérer les matières du cycle depuis la base de données
                    // CORRECTION : Utiliser 'cycle' au lieu de 'level_id' qui n'existe pas
                    $cycleSubjects = Subject::active()
                        ->where('cycle', $cycle)  // ✅ Utiliser le cycle (college, lycee, etc.)
                        ->orderBy('name')
                        ->get(['id', 'name']);
                    
                    // Si c'est le lycée, filtrer par série si spécifiée
                    if ($cycle === 'lycee' && $class->series) {
                        $cycleSubjects = $cycleSubjects->filter(function($subject) use ($class) {
                            return !$subject->series || $subject->series === $class->series;
                        });
                    }
                    
                    $subjects = $cycleSubjects;
                    Log::info("Matières du cycle {$cycle} trouvées: " . $subjects->count());
                }
            } catch (\Exception $e) {
                Log::warning('Erreur lors de la récupération des matières du cycle: ' . $e->getMessage());
            }

            // Si pas de matières trouvées dans la base, utiliser des données de base
            if ($subjects->isEmpty()) {
                Log::info('Aucune matière trouvée dans la base, utilisation des données de base');
                $subjects = collect([
                    ['id' => 1, 'name' => 'Français'],
                    ['id' => 2, 'name' => 'Mathématiques'],
                    ['id' => 3, 'name' => 'Histoire-Géo'],
                    ['id' => 4, 'name' => 'Sciences'],
                    ['id' => 5, 'name' => 'Anglais'],
                    ['id' => 6, 'name' => 'EPS'],
                    ['id' => 7, 'name' => 'Arts plastiques']
                ]);
            } else {
                Log::info('Matières du cycle trouvées: ' . $subjects->count());
            }

            // Créer le mapping matière -> professeurs (peut y avoir plusieurs profs par matière)
            $subjectTeachers = [];
            foreach ($subjects as $subject) {
                $subjectId = $subject['id'] ?? $subject->id;
                $subjectName = $subject['name'] ?? $subject->name;
                
                // Trouver TOUS les enseignants qui peuvent enseigner cette matière
                $subjectTeachersList = $teachers->filter(function($t) use ($subjectId) {
                    return $t->subjects->contains('id', $subjectId);
                });
                
                if ($subjectTeachersList->isNotEmpty()) {
                    // Créer la liste des professeurs pour cette matière
                    $teachersForSubject = $subjectTeachersList->map(function($t) {
                        return [
                            'id' => $t->id,
                            'name' => $t->first_name . ' ' . $t->last_name
                        ];
                    })->toArray();
                    
                    $subjectTeachers[] = [
                        'subject_id' => $subjectId,
                        'subject_name' => $subjectName,
                        'teachers' => $teachersForSubject,
                        'teacher_count' => count($teachersForSubject)
                    ];
                } else {
                    // Si pas d'enseignant trouvé, créer une entrée avec professeur par défaut
                    $defaultTeacher = $teachers->first();
                    $subjectTeachers[] = [
                        'subject_id' => $subjectId,
                        'subject_name' => $subjectName,
                        'teachers' => $defaultTeacher ? [
                            [
                                'id' => $defaultTeacher->id,
                                'name' => $defaultTeacher->first_name . ' ' . $defaultTeacher->last_name
                            ]
                        ] : [],
                        'teacher_count' => $defaultTeacher ? 1 : 0
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Matières et enseignants récupérés avec succès',
                'subjects' => $subjects,
                'teachers' => $teachers,
                'subjectTeachers' => $subjectTeachers
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans getClassSubjectsAndTeachers: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            // Fallback avec des données de base
            $subjects = collect([
                ['id' => 1, 'name' => 'Français'],
                ['id' => 2, 'name' => 'Mathématiques'],
                ['id' => 3, 'name' => 'Histoire-Géo'],
                ['id' => 4, 'name' => 'Sciences'],
                ['id' => 5, 'name' => 'Anglais'],
                ['id' => 6, 'name' => 'EPS'],
                ['id' => 7, 'name' => 'Arts plastiques']
            ]);
            
            $teachers = collect([
                ['id' => 1, 'first_name' => 'M.', 'last_name' => 'Dubois'],
                ['id' => 2, 'first_name' => 'Mme.', 'last_name' => 'Michel'],
                ['id' => 3, 'first_name' => 'M.', 'last_name' => 'Garcia'],
                ['id' => 4, 'first_name' => 'Mme.', 'last_name' => 'David'],
                ['id' => 5, 'first_name' => 'M.', 'last_name' => 'Robert']
            ]);
            
            $subjectTeachers = [];
            foreach ($subjects as $subject) {
                $teacher = $teachers->random();
                $subjectTeachers[] = [
                    'subject_id' => $subject['id'],
                    'subject_name' => $subject['name'],
                    'teacher_id' => $teacher['id'],
                    'teacher_name' => $teacher['first_name'] . ' ' . $teacher['last_name']
                ];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Données de base utilisées (erreur API)',
                'subjects' => $subjects,
                'teachers' => $teachers,
                'subjectTeachers' => $subjectTeachers
            ]);
        }
    }
}