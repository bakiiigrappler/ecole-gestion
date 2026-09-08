<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use App\Models\Concerns\GardeQuiSupprime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    /*
     * Suppression douce : effacer une fiche la met en corbeille au lieu de la
     * detruire. Le super administrateur peut l'y reprendre ou l'y detruire.
     */
    use AppartientAUnEtablissement, GardeQuiSupprime, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'qualification',
        'diploma_file',
        'specialization',
        'cycle',
        'teacher_type',
        'hire_date',
        'salary',
        'status',
        'photo'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'salary' => 'decimal:2',
    ];

    /**
     * Générer automatiquement un matricule enseignant
     */
    public static function generateEmployeeId()
    {
        $year = date('Y');
        
        // Format: ENS + YYYY + 4 chiffres
        $lastTeacher = static::where('employee_id', 'like', "ENS{$year}%")
                            ->orderBy('employee_id', 'desc')
                            ->first();
        
        $nextNumber = 1;
        if ($lastTeacher) {
            $lastNumber = intval(substr($lastTeacher->employee_id, -4));
            $nextNumber = $lastNumber + 1;
        }
        
        return "ENS{$year}" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /*
     * Les notes vivent dans `student_grades`, pas dans `grades` : cette
     * derniere table est vide et morte. La relation y pointait encore, et
     * tout ce qui la traversait comptait zero sans jamais lever d'erreur.
     */
    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    // Relation avec les horaires
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    // Relation avec la classe assignée (pour enseignants généralistes)
    /**
     * Classe dont l'enseignant est professeur principal.
     *
     * L'affectation vit sur le pivot `class_teacher` : elle decrit un lien, pas
     * l'enseignant. La colonne `teachers.assigned_class_id` a ete supprimee,
     * les deux niveaux ne se synchronisant jamais.
     */
    public function classePrincipale(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_teacher', 'teacher_id', 'class_id')
            ->wherePivot('role', 'principal')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Raccourci de lecture : $teacher->assignedClass reste disponible et
     * renvoie desormais la classe dont il est principal.
     */
    public function getAssignedClassAttribute(): ?SchoolClass
    {
        return $this->relationLoaded('classes')
            ? $this->classes->firstWhere('pivot.role', 'principal')
            : $this->classePrincipale->first();
    }

    // Relation many-to-many avec les matières
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_teacher')
                    ->withTimestamps();
    }

    // Relation many-to-many avec les classes
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_teacher', 'teacher_id', 'class_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    // Accesseur pour le nom complet
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Accesseur pour l'ancienneté
    public function getYearsOfServiceAttribute()
    {
        // Carbon 3 renvoie un flottant : sans cast, l'affichage montre
        // « 11.552058688198 ans ».
        return $this->hire_date ? (int) $this->hire_date->diffInYears(now()) : 0;
    }

    // Accesseur pour le type d'enseignant en français
    public function getTeacherTypeLabelAttribute()
    {
        return $this->teacher_type === 'general' ? 'Généraliste' : 'Spécialisé';
    }

    // Accesseur pour le cycle en français
    public function getCycleLabelAttribute()
    {
        $cycles = [
            'preprimaire' => 'Pré-primaire',
            'primaire' => 'Primaire',
            'college' => 'Collège',
            'lycee' => 'Lycée'
        ];
        return $cycles[$this->cycle] ?? $this->cycle;
    }

    // Scope pour les enseignants actifs
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Scope pour filtrer par cycle
    public function scopeByCycle($query, $cycle)
    {
        return $query->where('cycle', $cycle);
    }

    // Scope pour filtrer par type d'enseignant
    public function scopeByType($query, $type)
    {
        return $query->where('teacher_type', $type);
    }

    // Scope pour les enseignants généralistes
    public function scopeGeneral($query)
    {
        return $query->where('teacher_type', 'general');
    }

    // Scope pour les enseignants spécialisés
    public function scopeSpecialized($query)
    {
        return $query->where('teacher_type', 'specialized');
    }



    // Méthode pour vérifier si l'enseignant peut enseigner une matière
    public function canTeach($subjectId)
    {
        return $this->subjects()->where('subject_id', $subjectId)->exists();
    }

    // Scope pour les enseignants qui peuvent enseigner une matière spécifique
    public function scopeCanTeachSubject($query, $subjectId)
    {
        return $query->whereHas('subjects', function($q) use ($subjectId) {
            $q->where('subject_id', $subjectId);
        });
    }

    /**
     * Abréviations employées dans le champ libre `specialization` et matières
     * réelles qu'elles recouvrent. Table de rattrapage, pas une référence : elle
     * n'existe que parce que la spécialité est saisie en texte libre à côté de
     * la liaison `subject_teacher`, seule source fiable.
     */
    private const SPECIALITES_EQUIVALENTES = [
        'eps' => ['éducation physique et sportive'],
        'svt' => ['sciences de la vie et de la terre', 'biologie approfondie'],
        'physique-chimie' => ['sciences physiques'],
        'sciences' => ["sciences d'observation", 'sciences physiques'],
        'langues' => ['anglais', 'espagnol', 'latin', 'grec'],
    ];

    /**
     * L'enseignant couvre-t-il cette matière ? La réponse se lit d'abord dans la
     * liaison `subject_teacher`, puis à défaut dans le champ texte
     * `specialization` — les deux stockent le même fait sans se synchroniser,
     * et la liaison est aujourd'hui vide pour tout le monde.
     */
    public function couvreLaMatiere(Subject $matiere): bool
    {
        if ($this->subjects->contains('id', $matiere->id)) {
            return true;
        }

        if (! $this->specialization) {
            return false;
        }

        $specialite = mb_strtolower(trim($this->specialization));
        $nom = mb_strtolower($matiere->name);

        return $specialite === $nom
            || in_array($nom, self::SPECIALITES_EQUIVALENTES[$specialite] ?? [], true);
    }
}
