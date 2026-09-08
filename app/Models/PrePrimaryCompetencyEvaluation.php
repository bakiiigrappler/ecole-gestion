<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrePrimaryCompetencyEvaluation extends Model
{
    use AppartientAUnEtablissement;

    protected $fillable = [
        'student_id',
        'pre_primary_competency_id',
        'class_id',
        'academic_year_id',
        'teacher_id',
        'trimester_1_code',
        'trimester_2_code',
        'trimester_3_code',
        'trimester_1_comment',
        'trimester_2_comment',
        'trimester_3_comment'
    ];

    /**
     * Les codes d'évaluation possibles (alignés avec le système primaire)
     */
    const CODE_MAXIMALE = 'MAX';      // Compétence Acquise (Maîtrise Maximale)
    const CODE_MINIMALE = 'MIN';      // À Renforcer (Maîtrise Minimale)
    const CODE_PARTIELLE = 'PART';    // Non Acquise (Maîtrise Partielle)
    const CODE_NON_MAITRISE = 'NM';   // Encore Non Abordée (Non Maîtrise)

    /**
     * Relation: élève
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation: compétence
     */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(PrePrimaryCompetency::class, 'pre_primary_competency_id');
    }

    /**
     * Relation: classe
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Relation: année scolaire
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Relation: enseignant
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Scope: par élève
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope: par classe
     */
    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope: par année scolaire
     */
    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Obtenir le libellé d'un code d'évaluation
     */
    public static function getCodeLabel($code)
    {
        $labels = [
            self::CODE_MAXIMALE => 'Compétence Acquise',
            self::CODE_MINIMALE => 'À Renforcer',
            self::CODE_PARTIELLE => 'Non Acquise',
            self::CODE_NON_MAITRISE => 'Encore Non Abordée'
        ];

        return $labels[$code] ?? 'Non évalué';
    }

    /**
     * Obtenir la couleur associée à un code
     */
    public static function getCodeColor($code)
    {
        $colors = [
            self::CODE_MAXIMALE => '#d4edda',      // Vert clair
            self::CODE_MINIMALE => '#fff3cd',      // Jaune clair
            self::CODE_PARTIELLE => '#f8d7da',     // Rouge clair
            self::CODE_NON_MAITRISE => '#e9ecef'   // Gris clair
        ];

        return $colors[$code] ?? '#f8f9fa';
    }

    /**
     * Obtenir tous les codes possibles
     */
    public static function getAllCodes()
    {
        return [
            self::CODE_MAXIMALE,
            self::CODE_MINIMALE,
            self::CODE_PARTIELLE,
            self::CODE_NON_MAITRISE
        ];
    }

    /**
     * Vérifier si la compétence est acquise pour un trimestre donné
     */
    public function isAcquiredForTrimester($trimester)
    {
        $codeField = "trimester_{$trimester}_code";
        return $this->$codeField === self::CODE_MAXIMALE;
    }

    /**
     * Obtenir le taux de réussite pour une année (compétences acquises)
     */
    public static function getSuccessRateForStudent($studentId, $academicYearId)
    {
        $evaluations = self::byStudent($studentId)
            ->byAcademicYear($academicYearId)
            ->get();

        if ($evaluations->isEmpty()) {
            return 0;
        }

        $totalEvaluations = $evaluations->count() * 3; // 3 trimestres
        $acquiredCount = 0;

        foreach ($evaluations as $evaluation) {
            for ($t = 1; $t <= 3; $t++) {
                if ($evaluation->isAcquiredForTrimester($t)) {
                    $acquiredCount++;
                }
            }
        }

        return $totalEvaluations > 0 ? round(($acquiredCount / $totalEvaluations) * 100, 2) : 0;
    }
}
