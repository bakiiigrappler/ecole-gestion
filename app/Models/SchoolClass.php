<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use App\Models\Concerns\GardeQuiSupprime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolClass extends Model
{
    /*
     * Suppression douce : effacer une fiche la met en corbeille au lieu de la
     * detruire. Le super administrateur peut l'y reprendre ou l'y detruire.
     */
    use AppartientAUnEtablissement, GardeQuiSupprime, SoftDeletes;

    protected $table = 'classes';
    
    protected $fillable = [
        'name',
        'description',
        'capacity',
        'is_active',
        'level_id',
        'series_id',
    ];
    
    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relation avec le niveau
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    // Relation avec la serie du referentiel
    public function serie(): BelongsTo
    {
        return $this->belongsTo(Series::class, 'series_id');
    }

    /**
     * La serie s'ecrivait en clair dans une colonne texte. Les vues l'affichent
     * toujours ainsi : cet accesseur la lit desormais dans le referentiel, sans
     * qu'il faille reecrire chaque gabarit.
     */
    public function getSeriesAttribute()
    {
        return $this->serie?->name;
    }
    
    // Alias pour éviter le conflit avec la colonne level
    public function levelData(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    // Relation avec les horaires
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    // Relation avec les étudiants via les inscriptions
    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'class_id', 'student_id')
                    ->withPivot('academic_year_id', 'enrollment_date', 'status')
                    ->withTimestamps();
    }

    // Relation avec les professeurs généralistes assignés
    public function teachers()
    {
        return $this->hasMany(Teacher::class, 'assigned_class_id');
    }

    // Relation many-to-many avec tous les professeurs de la classe
    public function allTeachers()
    {
        return $this->belongsToMany(Teacher::class, 'class_teacher', 'class_id', 'teacher_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    // Relation avec les présences
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }

    // Relation avec les inscriptions
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    /*
     * Les notes vivent dans `student_grades`, pas dans `grades` : cette
     * derniere table est vide et morte. La relation y pointait encore, et
     * tout ce qui la traversait comptait zero sans jamais lever d'erreur.
     */
    public function grades()
    {
        return $this->hasMany(StudentGrade::class, 'class_id');
    }

    // Accesseur pour le nom complet avec niveau
    public function getFullNameAttribute()
    {
        $levelName = $this->levelData ? $this->levelData->name : '';
        return $this->name . ' - ' . $levelName;
    }

    // Scope pour les classes actives
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope pour filtrer par niveau
    public function scopeByLevel($query, $levelId)
    {
        return $query->where('level_id', $levelId);
    }

    // Scope pour filtrer par cycle
    public function scopeByCycle($query, $cycle)
    {
        return $query->whereHas('levelData', function($q) use ($cycle) {
            $q->where('cycle', $cycle);
        });
    }

    /**
     * Relation avec les frais de classe
     */
    public function classFees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClassFee::class, 'class_id');
    }

    /**
     * Obtenir le niveau de façon sûre (objet Level ou null)
     */
    public function getSafeLevel()
    {
        // Si level_id existe et qu'on a une relation, retourner la relation
        if ($this->level_id && $this->relationLoaded('levelData') && $this->levelData instanceof Level) {
            return $this->levelData;
        }
        
        // Si level_id existe mais pas de relation chargée, charger le niveau
        if ($this->level_id) {
            return Level::find($this->level_id);
        }
        
        return null;
    }

    /**
     * Obtenir le nom du niveau de façon sûre
     */
    public function getSafeLevelName()
    {
        $safeLevel = $this->getSafeLevel();
        return $safeLevel ? $safeLevel->name : 'Non défini';
    }

    /**
     * Obtenir le cycle de façon sûre
     */
    public function getSafeCycle()
    {
        $safeLevel = $this->getSafeLevel();
        if ($safeLevel) {
            return $safeLevel->cycle;
        }
        
        return 'non-defini';
    }

    /**
     * Obtenir le nombre d'élèves inscrits dans cette classe pour une année donnée
     */
    public function getEnrolledStudentsCount($academicYearId = null)
    {
        if (!$academicYearId) {
            $academicYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $academicYear ? $academicYear->id : null;
        }

        if (!$academicYearId) {
            return 0;
        }

        return $this->enrollments()
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Obtenir le nombre de places disponibles dans cette classe
     */
    public function getAvailablePlaces($academicYearId = null)
    {
        $enrolledCount = $this->getEnrolledStudentsCount($academicYearId);
        return max(0, $this->capacity - $enrolledCount);
    }

    /**
     * Vérifier si la classe a des places disponibles
     */
    public function hasAvailablePlaces($academicYearId = null)
    {
        return $this->getAvailablePlaces($academicYearId) > 0;
    }

    /**
     * Obtenir le pourcentage d'occupation de la classe
     */
    public function getOccupationPercentage($academicYearId = null)
    {
        if ($this->capacity == 0) {
            return 0;
        }
        
        $enrolledCount = $this->getEnrolledStudentsCount($academicYearId);
        return round(($enrolledCount / $this->capacity) * 100, 2);
    }

    /**
     * Base d'un nom de classe : le niveau, suivi de la lettre de serie si la
     * classe en porte une. « 1ere », « 1ere C ».
     */
    private static function baseDuNom(?int $levelId, ?int $seriesId = null): string
    {
        $niveau = $levelId ? Level::find($levelId) : null;

        if (! $niveau) {
            return '';
        }

        $base = $niveau->name;

        if ($seriesId && $serie = Series::find($seriesId)) {
            $base .= ' '.Series::lettreDuCode($serie->code);
        }

        return $base;
    }

    /**
     * Suffixes deja employes sur cette base : « 6eme 1 » et « 6eme A » donnent
     * « 1 » et « A ».
     */
    private static function suffixesUtilises(?int $levelId, string $base): array
    {
        if ($base === '') {
            return [];
        }

        // Les plus recemment creees d'abord : c'est la convention en cours.
        return self::where('level_id', $levelId)
            ->where('name', 'like', $base.' %')
            ->orderByDesc('id')
            ->pluck('name')
            ->map(fn ($nom) => mb_strtoupper(trim(mb_substr($nom, mb_strlen($base)))))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Numerotation deja en usage a ce niveau : « chiffre » ou « lettre ».
     *
     * On ne mélange pas « 6eme 1 » et « 6eme B » dans un meme niveau sans le
     * vouloir : la convention existante est proposee par defaut.
     */
    public static function numerotationDuNiveau(?int $levelId, ?int $seriesId = null): string
    {
        // La convention de la derniere classe creee : apres « 5eme A » et
        // « 5eme B », on propose « 5eme C », pas « 5eme 4 ».
        foreach (self::suffixesUtilises($levelId, self::baseDuNom($levelId, $seriesId)) as $suffixe) {
            if (ctype_digit($suffixe)) {
                return 'chiffre';
            }
            if (ctype_alpha($suffixe)) {
                return 'lettre';
            }
        }

        return 'chiffre';
    }

    /**
     * Nom propose pour une nouvelle classe.
     *
     * Les classes se nomment « <Niveau> <suffixe> », le suffixe etant un chiffre
     * (« 6eme 1 ») ou une lettre (« 6eme A ») selon l'usage de l'etablissement.
     * On reprend la premiere valeur encore libre plutot que de laisser
     * l'utilisateur deviner ou il en est.
     */
    public static function proposerNom(?int $levelId, ?int $seriesId = null, ?string $numerotation = null): string
    {
        $base = self::baseDuNom($levelId, $seriesId);

        if ($base === '') {
            return '';
        }

        $numerotation = in_array($numerotation, ['chiffre', 'lettre'], true)
            ? $numerotation
            : self::numerotationDuNiveau($levelId, $seriesId);

        $pris = self::suffixesUtilises($levelId, $base);

        if ($numerotation === 'lettre') {
            foreach (range('A', 'Z') as $lettre) {
                if (! in_array($lettre, $pris, true)) {
                    return $base.' '.$lettre;
                }
            }

            return $base.' AA';
        }

        for ($numero = 1; $numero <= 99; $numero++) {
            if (! in_array((string) $numero, $pris, true)) {
                return $base.' '.$numero;
            }
        }

        return $base.' 100';
    }
}
