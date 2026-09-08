<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use App\Models\Concerns\GardeQuiSupprime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    /*
     * Suppression douce : effacer une fiche la met en corbeille au lieu de la
     * detruire. Le super administrateur peut l'y reprendre ou l'y detruire.
     */
    use AppartientAUnEtablissement, GardeQuiSupprime, SoftDeletes;

    protected $fillable = [
        'student_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'place_of_birth',
        'address',
        'phone',
        'email',
        'emergency_contact',
        'medical_conditions',
        'fitness_status',
        'unfitness_reason',
        'photo',
        'enrollment_date',
        'status',
        'current_status',
        'total_enrollments',
        'total_redoublements',
        'first_enrollment_year_id',
        'last_enrollment_year_id',
        'has_been_enrolled',
        'last_enrollment_date',
        'history_comments'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
        'last_enrollment_date' => 'date',
        'has_been_enrolled' => 'boolean',
        'total_enrollments' => 'integer',
        'total_redoublements' => 'integer',
    ];

    /**
     * Générer automatiquement un matricule étudiant
     */
    public static function generateStudentId()
    {
        $year = date('Y');
        
        // Format: STU + YYYY + 4 chiffres
        $lastStudent = static::where('student_id', 'like', "STU{$year}%")
                            ->orderBy('student_id', 'desc')
                            ->first();
        
        $nextNumber = 1;
        if ($lastStudent) {
            $lastNumber = intval(substr($lastStudent->student_id, -4));
            $nextNumber = $lastNumber + 1;
        }
        
        return "STU{$year}" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relation avec les parents (many-to-many)
    public function parents(): BelongsToMany
    {
        // Les colonnes du pivot portent le lien de parente et le contact
        // principal : sans withPivot, la fiche eleve ne peut pas les afficher.
        return $this->belongsToMany(ParentModel::class, 'student_parent', 'student_id', 'parent_id')
            ->withPivot(['relationship_type', 'is_primary_contact', 'lives_with_student', 'can_pickup'])
            ->withTimestamps();
    }

    // Relation avec les inscriptions
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    // Relation avec les notes
    /*
     * Les notes vivent dans `student_grades`, pas dans `grades` : cette
     * derniere table est vide et morte. La relation y pointait encore, et
     * tout ce qui la traversait comptait zero sans jamais lever d'erreur.
     */
    /**
     * La photographie ne part qu'avec la fiche elle-meme.
     *
     * Tant que l'eleve est en corbeille, son fichier reste : c'est ce qui
     * permet de le restaurer entier. La suppression definitive, elle, ne doit
     * rien laisser derriere.
     */
    protected static function booted(): void
    {
        static::forceDeleted(function (self $eleve) {
            if ($eleve->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($eleve->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($eleve->photo);
            }
        });
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    // Relation avec les présences
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // Relation avec les paiements
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Accesseur pour le nom complet
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Accesseur pour l'âge
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    // Méthode pour obtenir la classe actuelle de manière sécurisée
    public function getCurrentClass()
    {
        $enrollment = $this->enrollments()
                          ->with('schoolClass.level')
                          ->where('status', 'active')
                          ->first();
        return $enrollment ? $enrollment->schoolClass : null;
    }

    // Méthode pour obtenir le niveau actuel de manière sécurisée
    public function getCurrentLevel()
    {
        $class = $this->getCurrentClass();
        if ($class && $class->level) {
            // Si level est une relation (objet), on le retourne
            if (is_object($class->level) && method_exists($class->level, 'name')) {
                return $class->level;
            }
            // Si level est une string, on crée un objet simple
            if (is_string($class->level)) {
                return (object) ['name' => $class->level];
            }
        }
        return null;
    }

    // Scope pour les élèves actifs
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    // Méthodes pour la gestion de l'historique et du statut
    
    /**
     * Mettre à jour les statistiques d'inscription de l'élève
     */
    public function updateEnrollmentStats()
    {
        $enrollments = $this->enrollments()->orderBy('academic_year_id')->get();
        
        if ($enrollments->isEmpty()) {
            $this->has_been_enrolled = false;
            $this->total_enrollments = 0;
            $this->total_redoublements = 0;
            $this->first_enrollment_year_id = null;
            $this->last_enrollment_year_id = null;
            $this->last_enrollment_date = null;
            $this->current_status = 'actif';
            $this->save();
            return;
        }
        
        // Compter le nombre total d'inscriptions
        $this->total_enrollments = $enrollments->count();
        
        // Compter le nombre de redoublements
        $this->total_redoublements = $enrollments->where('student_status', 'redoublant')->count();
        
        // Première et dernière inscription
        $firstEnrollment = $enrollments->first();
        $lastEnrollment = $enrollments->last();
        
        $this->first_enrollment_year_id = $firstEnrollment->academic_year_id;
        $this->last_enrollment_year_id = $lastEnrollment->academic_year_id;
        $this->last_enrollment_date = $lastEnrollment->enrollment_date;
        $this->has_been_enrolled = true;
        
        // Déterminer le statut actuel
        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
        
        if ($currentYear && $lastEnrollment->academic_year_id == $currentYear->id) {
            $this->current_status = 'actif';
        } else {
            $this->current_status = 'ancien';
        }
        
        $this->save();
    }
    
    /**
     * Obtenir l'historique complet des inscriptions
     */
    public function getEnrollmentHistory()
    {
        return $this->enrollments()
            ->with(['schoolClass.level', 'academicYear'])
            ->orderBy('academic_year_id', 'desc')
            ->get()
            ->map(function($enrollment) {
                return [
                    'enrollment_id' => $enrollment->id,
                    'year' => $enrollment->academicYear->name ?? 'N/A',
                    'class' => $enrollment->schoolClass->name ?? 'N/A',
                    'level' => $enrollment->schoolClass->level->name ?? 'N/A',
                    'cycle' => $enrollment->schoolClass->level->cycle ?? 'N/A',
                    'status' => $enrollment->student_status ?? 'N/A',
                    'result' => $enrollment->previous_year_result ?? 'N/A',
                    'average' => $enrollment->previous_year_average ?? 0,
                    'enrollment_date' => $enrollment->enrollment_date->format('d/m/Y')
                ];
            });
    }
    
    /**
     * Vérifier si l'élève est un ancien élève
     */
    public function isFormerStudent()
    {
        return $this->current_status === 'ancien';
    }
    
    /**
     * Vérifier si l'élève est actuellement actif
     */
    public function isCurrentlyActive()
    {
        return $this->current_status === 'actif';
    }
    
    /**
     * Obtenir le badge de statut actuel
     */
    public function getCurrentStatusBadgeAttribute()
    {
        return [
            'actif' => '<span class="badge bg-success">Actif</span>',
            'ancien' => '<span class="badge bg-secondary">Ancien élève</span>',
            'transfere' => '<span class="badge bg-warning">Transféré</span>',
            'diplome' => '<span class="badge bg-primary">Diplômé</span>'
        ][$this->current_status] ?? '<span class="badge bg-light">Non défini</span>';
    }
    
    /**
     * Obtenir le label du statut actuel
     */
    public function getCurrentStatusLabelAttribute()
    {
        return [
            'actif' => 'Actif',
            'ancien' => 'Ancien élève',
            'transfere' => 'Transféré',
            'diplome' => 'Diplômé'
        ][$this->current_status] ?? 'Non défini';
    }
    
    /**
     * Scope pour les anciens élèves
     */
    public function scopeFormerStudents($query)
    {
        return $query->where('current_status', 'ancien');
    }
    
    /**
     * Scope pour les élèves actuellement actifs
     */
    public function scopeCurrentlyActive($query)
    {
        return $query->where('current_status', 'actif');
    }
    
    /**
     * Scope pour les élèves qui ont déjà été inscrits
     */
    public function scopeHasBeenEnrolled($query)
    {
        return $query->where('has_been_enrolled', true);
    }
    
    /**
     * Obtenir un résumé de l'historique de l'élève
     */
    public function getHistorySummary()
    {
        $history = $this->getEnrollmentHistory();
        
        if ($history->isEmpty()) {
            return 'Aucune inscription enregistrée';
        }
        
        $summary = [];
        $summary[] = "Total inscriptions: {$this->total_enrollments}";
        
        if ($this->total_redoublements > 0) {
            $summary[] = "Redoublements: {$this->total_redoublements}";
        }
        
        $summary[] = "Statut: {$this->current_status_label}";
        
        if ($this->last_enrollment_date) {
            $summary[] = "Dernière inscription: {$this->last_enrollment_date->format('d/m/Y')}";
        }
        
        return implode(' | ', $summary);
    }
}
