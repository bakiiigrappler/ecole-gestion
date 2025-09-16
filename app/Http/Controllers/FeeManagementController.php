<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LevelFee;
use App\Models\ClassFee;
use App\Models\EnrollmentFee;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Services\FeeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeeManagementController extends Controller
{
    protected $feeService;

    public function __construct(FeeService $feeService)
    {
        $this->feeService = $feeService;
    }

    /**
     * Tableau de bord des frais
     */
    public function dashboard()
    {
        try {
            $academicYear = AcademicYear::where('is_current', true)->first();
            
            if (!$academicYear) {
                // Créer une année académique par défaut si aucune n'existe
                $academicYear = AcademicYear::create([
                    'name' => '2024-2025',
                    'start_date' => '2024-09-01',
                    'end_date' => '2025-08-31',
                    'is_current' => true
                ]);
            }

            $statistics = $this->feeService->getFeeStatistics($academicYear->id);
            $feesByLevel = $this->feeService->getFeesByLevel($academicYear->id);
            $feesByClass = $this->feeService->getFeesByClass($academicYear->id);

            // Frais échus
            $overdueFees = EnrollmentFee::where('academic_year_id', $academicYear->id)
                ->where('due_date', '<', now())
                ->where('is_paid', false)
                ->with(['enrollment.student', 'enrollment.schoolClass'])
                ->orderBy('due_date')
                ->limit(10)
                ->get();

            // Frais récents
            $recentFees = EnrollmentFee::where('academic_year_id', $academicYear->id)
                ->where('is_paid', true)
                ->with(['enrollment.student', 'enrollment.schoolClass'])
                ->orderBy('paid_at', 'desc')
                ->limit(10)
                ->get();

            return view('fees.dashboard', compact(
                'academicYear',
                'statistics',
                'feesByLevel',
                'feesByClass',
                'overdueFees',
                'recentFees'
            ));
            
        } catch (\Exception $e) {
            Log::error('Erreur dans FeeManagementController@dashboard: ' . $e->getMessage());
            
            // Retourner une vue avec des données par défaut en cas d'erreur
            $academicYear = (object) ['id' => 1, 'name' => '2024-2025'];
            $statistics = [
                'total_level_fees' => 0,
                'total_class_fees' => 0,
                'total_enrollment_fees' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'pending_amount' => 0,
                'collection_rate' => 0
            ];
            $feesByLevel = [];
            $feesByClass = [];
            $overdueFees = [];
            $recentFees = [];
            
            return view('fees.dashboard', compact(
                'academicYear',
                'statistics',
                'feesByLevel',
                'feesByClass',
                'overdueFees',
                'recentFees'
            ))->with('error', 'Erreur lors du chargement des données: ' . $e->getMessage());
        }
    }

    /**
     * Gestion des frais de niveau
     */
    public function levelFees()
    {
        $academicYear = AcademicYear::where('is_current', true)->first();
        
        if (!$academicYear) {
            return redirect()->route('academic-years.index')
                ->with('error', 'Aucune année académique active trouvée.');
        }

        $levelFees = LevelFee::with(['level', 'academicYear'])
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('level_id')
            ->orderBy('sort_order')
            ->get();

        $levels = Level::active()->orderBy('order')->get();

        return view('fees.level-fees', compact('levelFees', 'levels', 'academicYear'));
    }

    /**
     * Créer un frais de niveau
     */
    public function createLevelFee(Request $request)
    {
        $validated = $request->validate([
            'level_id' => 'required|exists:levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'fee_type' => 'required|in:tuition,registration,uniform,transport,meal,books,activities,other',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:monthly,quarterly,yearly,one_time',
            'due_date' => 'nullable|date',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0'
        ]);

        try {
            $levelFee = $this->feeService->createLevelFee($validated);

            return response()->json([
                'success' => true,
                'message' => 'Frais de niveau créé avec succès.',
                'level_fee' => $levelFee->load('level')
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating level fee', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du frais: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestion des frais de classe
     */
    public function classFees()
    {
        $academicYear = AcademicYear::where('is_current', true)->first();
        
        if (!$academicYear) {
            return redirect()->route('academic-years.index')
                ->with('error', 'Aucune année académique active trouvée.');
        }

        $classFees = ClassFee::with(['schoolClass.level', 'levelFee', 'academicYear'])
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('class_id')
            ->orderBy('sort_order')
            ->get();

        $classes = SchoolClass::with('level')
            ->orderBy('name')
            ->get();

        return view('fees.class-fees', compact('classFees', 'classes', 'academicYear'));
    }

    /**
     * Gestion des frais d'inscription
     */
    public function enrollmentFees(Request $request)
    {
        $academicYear = AcademicYear::where('is_current', true)->first();
        
        if (!$academicYear) {
            return redirect()->route('academic-years.index')
                ->with('error', 'Aucune année académique active trouvée.');
        }

        $query = EnrollmentFee::with([
            'enrollment.student',
            'enrollment.schoolClass.level',
            'levelFee',
            'classFee'
        ])->where('academic_year_id', $academicYear->id);

        // Filtres
        if ($request->filled('fee_type')) {
            $query->where('fee_type', $request->fee_type);
        }

        if ($request->filled('is_paid')) {
            $query->where('is_paid', $request->is_paid);
        }

        if ($request->filled('is_overdue')) {
            if ($request->is_overdue) {
                $query->where('due_date', '<', now())->where('is_paid', false);
            } else {
                $query->where(function($q) {
                    $q->where('due_date', '>=', now())
                      ->orWhere('is_paid', true);
                });
            }
        }

        if ($request->filled('class_id')) {
            $query->whereHas('enrollment', function($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        $enrollmentFees = $query->orderBy('due_date')->paginate(20);

        $classes = SchoolClass::with('level')
            ->orderBy('name')
            ->get();

        return view('fees.enrollment-fees', compact('enrollmentFees', 'classes', 'academicYear'));
    }

    /**
     * Détails d'un frais d'inscription
     */
    public function showEnrollmentFee(EnrollmentFee $enrollmentFee)
    {
        $enrollmentFee->load([
            'enrollment.student',
            'enrollment.schoolClass.level',
            'levelFee',
            'classFee',
            'payments'
        ]);

        return view('fees.enrollment-fee-details', compact('enrollmentFee'));
    }

    /**
     * Marquer un frais comme payé
     */
    public function markAsPaid(Request $request, EnrollmentFee $enrollmentFee)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $enrollmentFee->markAsPaid(
                $validated['payment_method'],
                $validated['payment_reference']
            );

            // Mettre à jour le total des frais de l'inscription
            $this->feeService->updateEnrollmentTotalFees($enrollmentFee->enrollment);

            return response()->json([
                'success' => true,
                'message' => 'Frais marqué comme payé avec succès.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking fee as paid', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer un frais comme non payé
     */
    public function markAsUnpaid(EnrollmentFee $enrollmentFee)
    {
        try {
            $enrollmentFee->markAsUnpaid();

            // Mettre à jour le total des frais de l'inscription
            $this->feeService->updateEnrollmentTotalFees($enrollmentFee->enrollment);

            return response()->json([
                'success' => true,
                'message' => 'Frais marqué comme non payé avec succès.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking fee as unpaid', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un rapport des frais
     */
    public function generateReport(Request $request)
    {
        $academicYear = AcademicYear::where('is_current', true)->first();
        
        if (!$academicYear) {
            return redirect()->back()
                ->with('error', 'Aucune année académique active trouvée.');
        }

        $filters = $request->only(['fee_type', 'is_paid', 'is_overdue', 'class_id']);
        $report = $this->feeService->generateFeeReport($academicYear->id, $filters);

        return view('fees.report', compact('report', 'academicYear'));
    }

    /**
     * Dupliquer les frais pour une nouvelle année
     */
    public function duplicateFees(Request $request)
    {
        $validated = $request->validate([
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id' => 'required|exists:academic_years,id'
        ]);

        try {
            $this->feeService->duplicateFeesForNewYear(
                $validated['from_academic_year_id'],
                $validated['to_academic_year_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Frais dupliqués avec succès pour la nouvelle année académique.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error duplicating fees', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la duplication: ' . $e->getMessage()
            ], 500);
        }
    }
}
