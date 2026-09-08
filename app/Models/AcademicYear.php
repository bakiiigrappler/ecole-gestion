<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use AppartientAUnEtablissement;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
        'status',
        'description'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean'
    ];

    // Relations
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    // Accesseurs
    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function getFormattedPeriodAttribute()
    {
        return $this->start_date->format('d/m/Y') . ' - ' . $this->end_date->format('d/m/Y');
    }

    // Méthodes utiles
    public function makeCurrent()
    {
        // Désactiver toutes les autres années courantes
        static::where('is_current', true)->update(['is_current' => false]);
        
        // Activer cette année
        $this->update(['is_current' => true]);
    }

    public function getDurationInDays()
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    public function isInProgress()
    {
        $now = now();
        return $now->greaterThanOrEqualTo($this->start_date) && 
               $now->lessThanOrEqualTo($this->end_date);
    }

    /**
     * Créer ou mettre à jour l'année scolaire actuelle selon le calendrier gabonais
     * Au Gabon, l'année scolaire va de septembre à juin
     */
    public static function updateCurrentAcademicYear()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Calculer le nom de l'année scolaire
        $yearName = ($currentMonth >= 9) 
            ? $currentYear . '-' . ($currentYear + 1)
            : ($currentYear - 1) . '-' . $currentYear;
        
        // Calculer les dates de début et fin
        if ($currentMonth >= 9) {
            $startDate = $currentYear . '-09-01';
            $endDate = ($currentYear + 1) . '-06-30';
        } else {
            $startDate = ($currentYear - 1) . '-09-01';
            $endDate = $currentYear . '-06-30';
        }
        
        // Vérifier si l'année existe déjà
        $academicYear = static::where('name', $yearName)->first();
        
        if ($academicYear) {
            // Mettre à jour l'année existante
            $academicYear->update([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_current' => true,
                'status' => 'active'
            ]);
        } else {
            // Créer une nouvelle année
            $academicYear = static::create([
                'name' => $yearName,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_current' => true,
                'status' => 'active',
                'description' => 'Année scolaire ' . $yearName
            ]);
        }
        
        // Désactiver toutes les autres années comme année courante
        static::where('id', '!=', $academicYear->id)
              ->where('is_current', true)
              ->update(['is_current' => false]);
        
        return $academicYear;
    }

    /**
     * Créer les 3 dernières et 3 prochaines années scolaires
     */
    public static function generateYears($yearsBack = 3, $yearsForward = 3)
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Calculer l'année de départ
        $startYear = ($currentMonth >= 9) ? $currentYear : ($currentYear - 1);
        
        // Générer les années
        for ($i = -$yearsBack; $i <= $yearsForward; $i++) {
            $year = $startYear + $i;
            $yearName = $year . '-' . ($year + 1);
            $startDate = $year . '-09-01';
            $endDate = ($year + 1) . '-06-30';
            
            // Vérifier si l'année existe déjà
            $exists = static::where('name', $yearName)->exists();
            
            if (!$exists) {
                static::create([
                    'name' => $yearName,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_current' => ($i === 0),
                    'status' => ($i === 0) ? 'active' : 'inactive',
                    'description' => 'Année scolaire ' . $yearName
                ]);
            }
        }
    }
}
