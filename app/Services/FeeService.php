<?php

namespace App\Services;

use App\Models\LevelFee;
use App\Models\ClassFee;
use App\Models\EnrollmentFee;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeeService
{
    /**
     * Créer un frais de niveau
     */
    public function createLevelFee(array $data): LevelFee
    {
        DB::beginTransaction();
        
        try {
            $levelFee = LevelFee::create($data);
            
            // Créer automatiquement les frais de classe
            $this->createClassFeesFromLevelFee($levelFee);
            
            DB::commit();
            
            Log::info('Level fee created successfully', ['level_fee_id' => $levelFee->id]);
            
            return $levelFee;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating level fee', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Créer des frais de classe à partir d'un frais de niveau
     */
    public function createClassFeesFromLevelFee(LevelFee $levelFee): void
    {
        $classes = SchoolClass::where('level_id', $levelFee->level_id)
            ->get();

        foreach ($classes as $class) {
            ClassFee::create([
                'level_fee_id' => $levelFee->id,
                'class_id' => $class->id,
                'academic_year_id' => $levelFee->academic_year_id,
                'fee_type' => $levelFee->fee_type,
                'name' => $levelFee->name . ' - ' . $class->name,
                'description' => $levelFee->description,
                'amount' => $levelFee->amount,
                'frequency' => $levelFee->frequency,
                'due_date' => $levelFee->due_date,
                'is_mandatory' => $levelFee->is_mandatory,
                'is_active' => $levelFee->is_active,
                'sort_order' => $levelFee->sort_order
            ]);
        }
    }

    /**
     * Assigner des frais à une inscription
     */
    public function assignFeesToEnrollment(Enrollment $enrollment): void
    {
        DB::beginTransaction();
        
        try {
            // Récupérer les frais de niveau applicables
            $levelFees = LevelFee::where('level_id', $enrollment->schoolClass->level_id)
                ->where('academic_year_id', $enrollment->academic_year_id)
                ->where('is_active', true)
                ->get();

            // Récupérer les frais de classe spécifiques
            $classFees = ClassFee::where('class_id', $enrollment->class_id)
                ->where('academic_year_id', $enrollment->academic_year_id)
                ->where('is_active', true)
                ->get();

            $allFees = $levelFees->merge($classFees);

            foreach ($allFees as $fee) {
                $this->createEnrollmentFee($enrollment, $fee);
            }

            // Mettre à jour le total des frais de l'inscription
            $this->updateEnrollmentTotalFees($enrollment);

            DB::commit();
            
            Log::info('Fees assigned to enrollment', ['enrollment_id' => $enrollment->id]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning fees to enrollment', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Créer un frais d'inscription
     */
    public function createEnrollmentFee(Enrollment $enrollment, $fee): EnrollmentFee
    {
        $feeData = [
            'enrollment_id' => $enrollment->id,
            'academic_year_id' => $enrollment->academic_year_id,
            'fee_type' => $fee->fee_type,
            'name' => $fee->name,
            'description' => $fee->description,
            'amount' => $fee->amount,
            'frequency' => $fee->frequency,
            'due_date' => $fee->due_date,
            'is_mandatory' => $fee->is_mandatory,
            'is_paid' => false,
            'notes' => null
        ];

        // Ajouter les relations appropriées
        if ($fee instanceof LevelFee) {
            $feeData['level_fee_id'] = $fee->id;
        } elseif ($fee instanceof ClassFee) {
            $feeData['class_fee_id'] = $fee->id;
        }

        return EnrollmentFee::create($feeData);
    }

    /**
     * Mettre à jour le total des frais d'une inscription
     */
    public function updateEnrollmentTotalFees(Enrollment $enrollment): void
    {
        $totalFees = $enrollment->enrollmentFees()->sum('amount');
        $amountPaid = $enrollment->enrollmentFees()
            ->where('is_paid', true)
            ->sum('amount');
        
        $enrollment->update([
            'total_fees' => $totalFees,
            'amount_paid' => $amountPaid,
            'balance_due' => $totalFees - $amountPaid
        ]);
    }

    /**
     * Obtenir les statistiques des frais
     */
    public function getFeeStatistics($academicYearId = null): array
    {
        $academicYearId = $academicYearId ?? AcademicYear::where('is_current', true)->first()?->id;

        if (!$academicYearId) {
            return [
                'total_fees' => 0,
                'total_collected' => 0,
                'collection_rate' => 0,
                'overdue_count' => 0,
                'pending_count' => 0
            ];
        }

        $totalFees = EnrollmentFee::where('academic_year_id', $academicYearId)->sum('amount');
        $totalCollected = EnrollmentFee::where('academic_year_id', $academicYearId)
            ->where('is_paid', true)
            ->sum('amount');
        $overdueCount = EnrollmentFee::where('academic_year_id', $academicYearId)
            ->where('due_date', '<', now())
            ->where('is_paid', false)
            ->count();
        $pendingCount = EnrollmentFee::where('academic_year_id', $academicYearId)
            ->where('is_paid', false)
            ->count();

        $collectionRate = $totalFees > 0 ? ($totalCollected / $totalFees) * 100 : 0;

        return [
            'total_fees' => $totalFees,
            'total_collected' => $totalCollected,
            'collection_rate' => round($collectionRate, 2),
            'overdue_count' => $overdueCount,
            'pending_count' => $pendingCount
        ];
    }

    /**
     * Obtenir les frais par niveau
     */
    public function getFeesByLevel($academicYearId = null): array
    {
        $academicYearId = $academicYearId ?? AcademicYear::where('is_current', true)->first()?->id;

        if (!$academicYearId) {
            return [];
        }

        $levels = Level::with(['levelFees' => function($query) use ($academicYearId) {
            $query->where('academic_year_id', $academicYearId)
                  ->where('is_active', true);
        }])->get();

        return $levels->map(function($level) {
            return [
                'level' => $level,
                'fees' => $level->levelFees,
                'total_amount' => $level->levelFees->sum('amount'),
                'fees_count' => $level->levelFees->count()
            ];
        })->toArray();
    }

    /**
     * Obtenir les frais par classe
     */
    public function getFeesByClass($academicYearId = null): array
    {
        $academicYearId = $academicYearId ?? AcademicYear::where('is_current', true)->first()?->id;

        if (!$academicYearId) {
            return [];
        }

        $classes = SchoolClass::with(['classFees' => function($query) use ($academicYearId) {
            $query->where('academic_year_id', $academicYearId)
                  ->where('is_active', true);
        }])->get();

        return $classes->map(function($class) {
            return [
                'class' => $class,
                'fees' => $class->classFees,
                'total_amount' => $class->classFees->sum('amount'),
                'fees_count' => $class->classFees->count()
            ];
        })->toArray();
    }

    /**
     * Obtenir les frais d'une inscription
     */
    public function getEnrollmentFees(Enrollment $enrollment): array
    {
        $enrollmentFees = $enrollment->enrollmentFees()
            ->with(['levelFee', 'classFee'])
            ->orderBy('fee_type')
            ->orderBy('due_date')
            ->get();

        $groupedFees = $enrollmentFees->groupBy('fee_type');

        return [
            'enrollment' => $enrollment,
            'fees' => $enrollmentFees,
            'grouped_fees' => $groupedFees,
            'total_amount' => $enrollmentFees->sum('amount'),
            'paid_amount' => $enrollmentFees->where('is_paid', true)->sum('amount'),
            'remaining_amount' => $enrollmentFees->where('is_paid', false)->sum('amount'),
            'overdue_count' => $enrollmentFees->where('due_date', '<', now())->where('is_paid', false)->count()
        ];
    }

    /**
     * Générer un rapport des frais
     */
    public function generateFeeReport($academicYearId = null, $filters = []): array
    {
        $academicYearId = $academicYearId ?? AcademicYear::where('is_current', true)->first()?->id;

        if (!$academicYearId) {
            return [];
        }

        $query = EnrollmentFee::where('academic_year_id', $academicYearId)
            ->with(['enrollment.student', 'enrollment.schoolClass', 'levelFee', 'classFee']);

        // Appliquer les filtres
        if (isset($filters['fee_type'])) {
            $query->where('fee_type', $filters['fee_type']);
        }

        if (isset($filters['is_paid'])) {
            $query->where('is_paid', $filters['is_paid']);
        }

        if (isset($filters['is_overdue'])) {
            if ($filters['is_overdue']) {
                $query->where('due_date', '<', now())->where('is_paid', false);
            } else {
                $query->where(function($q) {
                    $q->where('due_date', '>=', now())
                      ->orWhere('is_paid', true);
                });
            }
        }

        $enrollmentFees = $query->get();

        return [
            'academic_year_id' => $academicYearId,
            'filters' => $filters,
            'enrollment_fees' => $enrollmentFees,
            'classes' => SchoolClass::with('level')->orderBy('name')->get(),
            'statistics' => $this->getFeeStatistics($academicYearId),
            'generated_at' => now()
        ];
    }

    /**
     * Dupliquer les frais pour une nouvelle année académique
     */
    public function duplicateFeesForNewYear($fromAcademicYearId, $toAcademicYearId): void
    {
        DB::beginTransaction();
        
        try {
            $levelFees = LevelFee::where('academic_year_id', $fromAcademicYearId)->get();

            foreach ($levelFees as $levelFee) {
                $newLevelFee = $levelFee->replicate();
                $newLevelFee->academic_year_id = $toAcademicYearId;
                $newLevelFee->save();

                $this->createClassFeesFromLevelFee($newLevelFee);
            }

            DB::commit();
            
            Log::info('Fees duplicated for new academic year', [
                'from_year' => $fromAcademicYearId,
                'to_year' => $toAcademicYearId
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error duplicating fees for new academic year', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
