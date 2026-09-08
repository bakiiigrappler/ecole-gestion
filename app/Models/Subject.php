<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use App\Models\Concerns\GardeQuiSupprime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    /*
     * Suppression douce : effacer une fiche la met en corbeille au lieu de la
     * detruire. Le super administrateur peut l'y reprendre ou l'y detruire.
     */
    use AppartientAUnEtablissement, GardeQuiSupprime, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'cycle',
        'series',
        'coefficient',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        // La colonne est un entier : le cast `decimal:2` renvoyait « 2.00 », que
        // PostgreSQL refusait ensuite a l'ecriture. Aucune creation ne passait.
        'coefficient' => 'integer',
        'series' => 'array'
    ];

    /**
     * Notes de la matiere.
     *
     * Visait `Grade` — la table `grades`, vide et morte — alors que toutes les
     * notes sont dans `student_grades`. Le controle avant suppression comptait
     * donc systematiquement zero : une matiere portant 360 notes se supprimait
     * sans avertissement.
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

    // Relation many-to-many avec les enseignants
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'subject_teacher')
                    ->withTimestamps();
    }

    // Accesseur pour le nom complet avec cycle
    public function getFullNameAttribute()
    {
        return $this->name . ' (' . ucfirst($this->cycle) . ')';
    }

    // Scope pour les matières actives
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope pour filtrer par cycle
    public function scopeByCycle($query, $cycle)
    {
        return $query->where('cycle', $cycle);
    }

    // Scope pour filtrer par série (pour le lycée)
    public function scopeBySeries($query, $series)
    {
        return $query->whereJsonContains('series', $series);
    }

    // Méthode pour vérifier si une matière est applicable à une série donnée
    public function isApplicableToSeries($series)
    {
        if ($this->cycle !== 'lycee') {
            return true; // Les matières non-lycée s'appliquent partout
        }
        
        return in_array($series, $this->series ?? []);
    }



    // Méthode pour obtenir les enseignants actifs qui peuvent enseigner cette matière
    public function getAvailableTeachers()
    {
        return $this->teachers()->active()->get();
    }

    /** Suffixe de code par cycle, tel qu'employe dans le programme existant. */
    private const SUFFIXES_CYCLE = [
        'primaire' => 'PRIM',
        'college' => 'COL',
        'lycee' => 'LYC',
    ];

    /** Mots vides ignores dans la construction de l'abreviation. */
    private const MOTS_VIDES = ['de', 'du', 'des', 'la', 'le', 'les', 'et', 'a', 'aux', 'en', 'l', 'd'];

    /**
     * Abreviation d'un intitule de matiere, sans le suffixe de cycle.
     *
     * Plusieurs mots significatifs donnent leurs initiales — « Sciences de la Vie
     * et de la Terre » devient SVT, « Education physique et sportive » EPS. Un mot
     * unique est tronque a quatre lettres.
     */
    public static function abregerNom(string $nom): string
    {
        $sansAccent = mb_strtoupper(
            preg_replace('/[^A-Za-z0-9 ]/', ' ', self::sansAccents($nom))
        );

        $mots = array_values(array_filter(
            preg_split('/\s+/', trim($sansAccent)) ?: [],
            fn ($m) => $m !== '' && ! in_array(mb_strtolower($m), self::MOTS_VIDES, true)
        ));

        if ($mots === []) {
            return 'MAT';
        }

        if (count($mots) === 1) {
            return mb_substr($mots[0], 0, 4);
        }

        return mb_substr(implode('', array_map(fn ($m) => mb_substr($m, 0, 1), $mots)), 0, 6);
    }

    /**
     * Code unique propose pour une matiere. Le code n'est plus saisi a la main :
     * il se deduit de l'intitule et du cycle, et un suffixe numerique est ajoute
     * si la combinaison est deja prise.
     */
    public static function genererCode(string $nom, ?string $cycle, ?int $ignorerId = null): string
    {
        $base = self::abregerNom($nom);
        $suffixe = self::SUFFIXES_CYCLE[$cycle] ?? null;
        $candidat = $suffixe ? $base.'_'.$suffixe : $base;
        $candidat = mb_substr($candidat, 0, 50);

        $existe = fn ($code) => self::where('code', $code)
            ->when($ignorerId, fn ($q) => $q->where('id', '!=', $ignorerId))
            ->exists();

        if (! $existe($candidat)) {
            return $candidat;
        }

        for ($n = 2; $n <= 99; $n++) {
            $essai = mb_substr($candidat, 0, 47).'_'.$n;
            if (! $existe($essai)) {
                return $essai;
            }
        }

        return mb_substr($candidat, 0, 42).'_'.uniqid();
    }

    private static function sansAccents(string $texte): string
    {
        return strtr($texte, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a',
            'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ó' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ÿ' => 'y', 'ñ' => 'n', 'œ' => 'oe', 'æ' => 'ae',
            'À' => 'A', 'Â' => 'A', 'Ä' => 'A', 'Á' => 'A',
            'Ç' => 'C',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Î' => 'I', 'Ï' => 'I', 'Í' => 'I',
            'Ô' => 'O', 'Ö' => 'O', 'Ó' => 'O',
            'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ú' => 'U',
            'Ÿ' => 'Y', 'Ñ' => 'N', 'Œ' => 'OE', 'Æ' => 'AE',
        ]);
    }
}
