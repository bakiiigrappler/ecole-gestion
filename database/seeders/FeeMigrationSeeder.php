<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fee;
use App\Models\LevelFee;
use App\Models\ClassFee;
use App\Models\Enrollment;
use App\Models\EnrollmentFee;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeeMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Début de la migration des frais...');
        
        DB::beginTransaction();
        
        try {
            // Récupérer l'année académique actuelle
            $academicYear = AcademicYear::where('is_current', true)->first();
            
            if (!$academicYear) {
                $this->command->error('Aucune année académique active trouvée.');
                return;
            }
            
            $this->command->info("Migration pour l'année académique: {$academicYear->name}");
            
            // Migrer les frais existants vers la nouvelle structure
            $this->migrateExistingFees($academicYear);
            
            // Assigner les frais aux inscriptions existantes
            $this->assignFeesToExistingEnrollments($academicYear);
            
            DB::commit();
            
            $this->command->info('Migration des frais terminée avec succès !');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Erreur lors de la migration: ' . $e->getMessage());
            Log::error('Fee migration error', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Migrer les frais existants vers la nouvelle structure
     */
    private function migrateExistingFees(AcademicYear $academicYear)
    {
        $this->command->info('Migration des frais existants...');
        
        $existingFees = Fee::where('academic_year_id', $academicYear->id)->get();
        
        foreach ($existingFees as $fee) {
            // Si le frais est lié à une classe spécifique
            if ($fee->class_id) {
                $this->migrateClassFee($fee, $academicYear);
            } else {
                // Si le frais est lié à un niveau (class_id = null)
                $this->migrateLevelFee($fee, $academicYear);
            }
        }
        
        $this->command->info("Migré {$existingFees->count()} frais existants");
    }
    
    /**
     * Migrer un frais de niveau
     */
    private function migrateLevelFee(Fee $fee, AcademicYear $academicYear)
    {
        // Déterminer le niveau basé sur le nom du frais ou utiliser un niveau par défaut
        $level = $this->determineLevelFromFee($fee);
        
        if (!$level) {
            $this->command->warn("Impossible de déterminer le niveau pour le frais: {$fee->name}");
            return;
        }
        
        // Créer le frais de niveau
        $levelFee = LevelFee::create([
            'level_id' => $level->id,
            'academic_year_id' => $academicYear->id,
            'fee_type' => $fee->fee_type,
            'name' => $fee->name,
            'description' => $fee->description,
            'amount' => $fee->amount,
            'frequency' => $fee->frequency,
            'due_date' => $fee->due_date,
            'is_mandatory' => $fee->is_mandatory,
            'is_active' => $fee->is_active,
            'sort_order' => 0
        ]);
        
        // Créer les frais de classe dérivés
        $this->createClassFeesFromLevelFee($levelFee, $academicYear);
        
        $this->command->info("Migré frais de niveau: {$fee->name} -> {$level->name}");
    }
    
    /**
     * Migrer un frais de classe
     */
    private function migrateClassFee(Fee $fee, AcademicYear $academicYear)
    {
        $class = SchoolClass::find($fee->class_id);
        
        if (!$class) {
            $this->command->warn("Classe introuvable pour le frais: {$fee->name}");
            return;
        }
        
        // Créer le frais de classe
        ClassFee::create([
            'level_fee_id' => null, // Pas de frais de niveau parent
            'class_id' => $class->id,
            'academic_year_id' => $academicYear->id,
            'fee_type' => $fee->fee_type,
            'name' => $fee->name,
            'description' => $fee->description,
            'amount' => $fee->amount,
            'frequency' => $fee->frequency,
            'due_date' => $fee->due_date,
            'is_mandatory' => $fee->is_mandatory,
            'is_active' => $fee->is_active,
            'sort_order' => 0,
            'additional_amount' => 0,
            'discount_amount' => 0
        ]);
        
        $this->command->info("Migré frais de classe: {$fee->name} -> {$class->name}");
    }
    
    /**
     * Créer les frais de classe à partir d'un frais de niveau
     */
    private function createClassFeesFromLevelFee(LevelFee $levelFee, AcademicYear $academicYear)
    {
        $classes = SchoolClass::where('level_id', $levelFee->level_id)
            ->get();
        
        foreach ($classes as $class) {
            ClassFee::create([
                'level_fee_id' => $levelFee->id,
                'class_id' => $class->id,
                'academic_year_id' => $academicYear->id,
                'fee_type' => $levelFee->fee_type,
                'name' => $levelFee->name . ' - ' . $class->name,
                'description' => $levelFee->description,
                'amount' => $levelFee->amount,
                'frequency' => $levelFee->frequency,
                'due_date' => $levelFee->due_date,
                'is_mandatory' => $levelFee->is_mandatory,
                'is_active' => $levelFee->is_active,
                'sort_order' => $levelFee->sort_order,
                'additional_amount' => 0,
                'discount_amount' => 0
            ]);
        }
    }
    
    /**
     * Déterminer le niveau basé sur le nom du frais
     */
    private function determineLevelFromFee(Fee $fee)
    {
        $levels = Level::active()->get();
        
        // Rechercher par mots-clés dans le nom
        $feeName = strtolower($fee->name);
        
        foreach ($levels as $level) {
            $levelName = strtolower($level->name);
            
            if (str_contains($feeName, $levelName) || str_contains($feeName, strtolower($level->cycle))) {
                return $level;
            }
        }
        
        // Si aucun niveau trouvé, utiliser le premier niveau actif
        return $levels->first();
    }
    
    /**
     * Assigner les frais aux inscriptions existantes
     */
    private function assignFeesToExistingEnrollments(AcademicYear $academicYear)
    {
        $this->command->info('Assignment des frais aux inscriptions existantes...');
        
        $enrollments = Enrollment::where('academic_year_id', $academicYear->id)
            ->where('enrollment_status', 'active')
            ->with(['schoolClass.level'])
            ->get();
        
        $assignedCount = 0;
        
        foreach ($enrollments as $enrollment) {
            // Récupérer les frais de niveau applicables
            $levelFees = LevelFee::where('level_id', $enrollment->schoolClass->level_id)
                ->where('academic_year_id', $academicYear->id)
                ->where('is_active', true)
                ->get();
            
            // Récupérer les frais de classe spécifiques
            $classFees = ClassFee::where('class_id', $enrollment->class_id)
                ->where('academic_year_id', $academicYear->id)
                ->where('is_active', true)
                ->get();
            
            $allFees = $levelFees->merge($classFees);
            
            foreach ($allFees as $fee) {
                // Vérifier si le frais d'inscription existe déjà
                $existingEnrollmentFee = EnrollmentFee::where('enrollment_id', $enrollment->id)
                    ->where(function($query) use ($fee) {
                        if ($fee instanceof LevelFee) {
                            $query->where('level_fee_id', $fee->id);
                        } else {
                            $query->where('class_fee_id', $fee->id);
                        }
                    })
                    ->first();
                
                if (!$existingEnrollmentFee) {
                    $this->createEnrollmentFee($enrollment, $fee);
                    $assignedCount++;
                }
            }
        }
        
        $this->command->info("Assigné {$assignedCount} frais aux inscriptions");
    }
    
    /**
     * Créer un frais d'inscription
     */
    private function createEnrollmentFee(Enrollment $enrollment, $fee)
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
        
        EnrollmentFee::create($feeData);
    }
}