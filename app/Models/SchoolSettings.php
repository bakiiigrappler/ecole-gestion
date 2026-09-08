<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolSettings extends Model
{
    use AppartientAUnEtablissement, HasFactory;

    protected $fillable = [
        // Sans cette entree, un rattachement passe explicitement a create()
        // serait silencieusement ignore par l'affectation de masse.
        'school_id',
        'school_name',
        'primary_school_name',
        'secondary_school_name',
        'school_address',
        'school_phone',
        'school_email',
        'school_website',
        'school_bp',
        'school_logo',
        'school_seal',
        'school_motto',
        'school_description',
        'principal_name',
        'principal_title',
        'academic_year',
        'school_type',
        'school_level',
        'has_preprimary',
        'has_primary',
        'has_secondary',
        'country',
        'city',
        'timezone',
        'currency',
        'language',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_preprimary' => 'boolean',
        'has_primary' => 'boolean',
        'has_secondary' => 'boolean',
    ];

    /**
     * Boot method
     * Synchroniser has_preprimary avec has_primary car ils forment un seul groupe
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($settings) {
            // Si has_primary est activé/désactivé, synchroniser has_preprimary
            if (isset($settings->has_primary)) {
                $settings->has_preprimary = $settings->has_primary;
            }
        });

        // Toute ecriture invalide la memorisation, sinon la requete en cours
        // continuerait a servir les anciennes valeurs.
        static::saved(fn () => static::oublierMemorisation());
        static::deleted(fn () => static::oublierMemorisation());
    }

    /**
     * Paramètres mémorisés le temps de la requête HTTP.
     *
     * Le view composer '*' d'AppServiceProvider appelle getSettings() pour
     * chaque vue ET chaque composant Blade rendu : sans mémorisation, une page
     * comportant 50 lignes de tableau déclenchait plus de 400 allers-retours
     * vers la base pour lire la même ligne.
     */
    protected static array $memorises = [];

    /**
     * Obtenir les paramètres de l'école
     */
    public static function getSettings()
    {
        // La memorisation est desormais par etablissement : une cle unique
        // servirait les parametres d'une ecole a une autre.
        $cle = (string) (\App\Support\EcoleCourante::id() ?? 'global');

        if (isset(static::$memorises[$cle])) {
            return static::$memorises[$cle];
        }

        $settings = self::where('is_active', true)->first();
        
        if (!$settings) {
            // Créer des paramètres par défaut si aucun n'existe
            $settings = self::create([
                'school_name' => 'Lycée XXXXX',
                'school_phone' => '06037499',
                'school_bp' => 'BP: 6',
                'principal_title' => 'Le Proviseur',
                'academic_year' => '2024-2025',
                'school_type' => 'Lycée',
                'school_level' => 'Secondaire',
                'country' => 'Gabon',
                'city' => 'Libreville',
                'timezone' => 'Africa/Libreville',
                'currency' => 'FCFA',
                'language' => 'fr',
                'is_active' => true
            ]);
        }

        return static::$memorises[$cle] = $settings;
    }

    /**
     * Vide la mémorisation : à appeler après toute écriture sur les paramètres.
     */
    public static function oublierMemorisation(): void
    {
        static::$memorises = [];
    }

    /**
     * Obtenir l'URL du logo
     */
    public function getLogoUrlAttribute()
    {
        // asset() s'appuie sur APP_URL : l'ancienne version codait en dur le
        // port 8000, ce qui cassait l'image des qu'on servait ailleurs.
        return $this->school_logo
            ? asset('storage/'.$this->school_logo)
            : null;
    }

    /**
     * Obtenir l'URL du sceau
     */
    public function getSealUrlAttribute()
    {
        // asset() s'appuie sur APP_URL : l'ancienne version codait en dur le
        // port 8000, ce qui cassait l'image des qu'on servait ailleurs.
        return $this->school_seal
            ? asset('storage/'.$this->school_seal)
            : null;
    }

    /**
     * Obtenir le nom de l'école selon le cycle
     * 
     * @param string $cycle Le cycle (preprimaire, primaire, college, lycee)
     * @return string Le nom approprié de l'établissement
     */
    public function getSchoolNameByCycle($cycle)
    {
        $cycle = strtolower($cycle);
        
        // Pour le préprimaire et primaire, utiliser le nom du complexe scolaire
        if (in_array($cycle, ['preprimaire', 'primaire'])) {
            return $this->primary_school_name ?: 'Complexe Scolaire';
        }
        
        // Pour le collège et lycée, utiliser le nom du secondaire
        if (in_array($cycle, ['college', 'collège', 'lycee', 'lycée', 'secondaire'])) {
            return $this->secondary_school_name ?: 'Lycée';
        }
        
        // Par défaut, retourner le nom du primaire (si défini) sinon celui du secondaire
        return $this->primary_school_name ?: $this->secondary_school_name ?: 'Établissement Scolaire';
    }

    /**
     * Obtenir le nom de l'école selon le niveau (Level model)
     * 
     * @param mixed $level Instance de Level ou ID de niveau
     * @return string Le nom approprié de l'établissement
     */
    public function getSchoolNameByLevel($level)
    {
        if (is_numeric($level)) {
            $level = \App\Models\Level::find($level);
        }
        
        if ($level && $level->cycle) {
            return $this->getSchoolNameByCycle($level->cycle);
        }
        
        // Par défaut, retourner le premier nom disponible
        return $this->primary_school_name ?: $this->secondary_school_name ?: 'Établissement Scolaire';
    }

    /**
     * Vérifier si un niveau est actif
     * 
     * Note : Préprimaire et Primaire sont liés (un seul groupe)
     * Note : Collège et Lycée sont liés (un seul groupe)
     * 
     * @param string $cycle Le cycle à vérifier
     * @return bool
     */
    public function isLevelActive($cycle)
    {
        $cycle = strtolower($cycle);
        
        // Préprimaire et Primaire sont liés - vérifier has_primary pour les deux
        if ($cycle === 'preprimaire' || $cycle === 'primaire') {
            return $this->has_primary;
        }
        
        // Collège et Lycée sont liés - vérifier has_secondary pour les deux
        if (in_array($cycle, ['college', 'collège', 'lycee', 'lycée', 'secondaire'])) {
            return $this->has_secondary;
        }
        
        return true; // Par défaut, considérer comme actif
    }

    /**
     * Obtenir les niveaux actifs
     * 
     * Note : Préprimaire et Primaire sont toujours liés (activés/désactivés ensemble)
     * Note : Collège et Lycée sont toujours liés (activés/désactivés ensemble)
     * 
     * @return array
     */
    public function getActiveLevels()
    {
        $levels = [];
        
        // Préprimaire et Primaire sont liés - si has_primary est actif, les deux le sont
        if ($this->has_primary) {
            $levels[] = 'preprimaire';
            $levels[] = 'primaire';
        }
        
        // Collège et Lycée sont liés - si has_secondary est actif, les deux le sont
        if ($this->has_secondary) {
            $levels[] = 'college';
            $levels[] = 'lycee';
        }
        
        return $levels;
    }
}
