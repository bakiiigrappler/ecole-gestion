<?php

namespace App\Support;

use App\Models\Schedule;
use App\Models\Teacher;
use Illuminate\Support\Collection;

/**
 * Ce qu'un enseignant a le droit de voir.
 *
 * Son périmètre se lit dans l'emploi du temps : les créneaux qui portent son
 * identifiant disent quelles classes il a, quelles matières il y enseigne et à
 * quelles heures. C'est la seule source qui décrive les trois à la fois — le
 * pivot `class_teacher` ne dit pas la matière, `subject_teacher` ne dit pas la
 * classe.
 *
 * Le pivot `class_teacher` complète pour le professeur principal, qui suit sa
 * classe au-delà de ses propres heures.
 */
class PerimetreEnseignant
{
    /** Mémorisé par requête : ces jointures reviennent à chaque page. */
    private static array $memorise = [];

    /**
     * La fiche enseignant du compte connecté, s'il en a une.
     */
    public static function enseignant(): ?Teacher
    {
        $utilisateur = auth()->user();

        if (! $utilisateur || $utilisateur->role !== 'teacher') {
            return null;
        }

        return static::$memorise['fiche'] ??= Teacher::where('user_id', $utilisateur->id)->first();
    }

    public static function estEnseignant(): bool
    {
        return auth()->user()?->role === 'teacher';
    }

    /**
     * Les créneaux de l'emploi du temps qui lui appartiennent.
     */
    public static function creneaux(): Collection
    {
        $enseignant = self::enseignant();

        if (! $enseignant) {
            return collect();
        }

        return static::$memorise['creneaux'] ??= Schedule::where('teacher_id', $enseignant->id)
            ->get(['id', 'class_id', 'subject_id', 'day_of_week', 'start_time', 'end_time', 'type']);
    }

    /**
     * Identifiants des classes où il intervient.
     *
     * Ses heures de cours, plus les classes dont il est professeur principal.
     */
    public static function classes(): array
    {
        if (isset(static::$memorise['classes'])) {
            return static::$memorise['classes'];
        }

        $enseignant = self::enseignant();

        if (! $enseignant) {
            return static::$memorise['classes'] = [];
        }

        $parLEmploiDuTemps = self::creneaux()->pluck('class_id')->filter();
        $parLeSuivi = $enseignant->classes()->pluck('classes.id');

        return static::$memorise['classes'] = $parLEmploiDuTemps
            ->merge($parLeSuivi)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Identifiants des matières qu'il enseigne.
     */
    public static function matieres(): array
    {
        if (isset(static::$memorise['matieres'])) {
            return static::$memorise['matieres'];
        }

        $enseignant = self::enseignant();

        if (! $enseignant) {
            return static::$memorise['matieres'] = [];
        }

        return static::$memorise['matieres'] = self::creneaux()
            ->pluck('subject_id')
            ->filter()
            ->merge($enseignant->subjects()->pluck('subjects.id'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Les matières qu'il enseigne dans une classe donnée.
     *
     * Une note ne se saisit que là : sa classe, et sa matière dans cette classe.
     */
    public static function matieresDansLaClasse(int $classeId): array
    {
        return self::creneaux()
            ->where('class_id', $classeId)
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * A-t-il cours dans cette classe ce jour-là ?
     *
     * L'appel se fait par séance : un enseignant ne pointe que ses heures.
     */
    public static function aCoursCeJour(int $classeId, $date): bool
    {
        $jour = \Carbon\Carbon::parse($date)->dayOfWeekIso;

        return self::creneaux()
            ->where('class_id', $classeId)
            ->where('day_of_week', $jour)
            ->where('type', 'course')
            ->isNotEmpty();
    }

    /**
     * Les jours de la semaine (ISO) où il a cours dans une classe.
     */
    public static function joursDansLaClasse(int $classeId): array
    {
        return self::creneaux()
            ->where('class_id', $classeId)
            ->where('type', 'course')
            ->pluck('day_of_week')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public static function oublier(): void
    {
        static::$memorise = [];
    }
}
