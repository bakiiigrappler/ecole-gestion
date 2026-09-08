<?php

namespace App\Support;

use App\Models\ParentModel;
use App\Models\Student;

/**
 * Qui a le droit de consulter le dossier d'un élève.
 *
 * Le bulletin, le reçu, la fiche : ces pages prennent un identifiant d'élève
 * dans l'URL. Sans vérification, un compte élève ouvrait le bulletin de son
 * voisin en changeant un chiffre, et un parent celui de n'importe quel enfant
 * de l'établissement.
 *
 * La règle est simple : l'administration voit tout, l'enseignant voit ses
 * classes, le parent ses enfants, l'élève lui-même.
 */
class AccesEleve
{
    public static function autorise(Student $eleve): bool
    {
        $utilisateur = auth()->user();

        if (! $utilisateur) {
            return false;
        }

        return match ($utilisateur->role) {
            'student' => (int) $eleve->user_id === (int) $utilisateur->id,
            'parent' => self::estSonEnfant($eleve, $utilisateur->id),
            'teacher' => self::estDansSesClasses($eleve),
            default => true,   // superadmin, admin, secrétariat
        };
    }

    /**
     * Refuse l'accès si l'élève ne relève pas de la personne connectée.
     */
    public static function verifier(Student $eleve): void
    {
        abort_unless(self::autorise($eleve), 403,
            'Ce dossier ne relève pas de votre compte.');
    }

    private static function estSonEnfant(Student $eleve, int $utilisateurId): bool
    {
        $parent = ParentModel::where('user_id', $utilisateurId)->first();

        return $parent
            && $parent->students()->where('students.id', $eleve->id)->exists();
    }

    private static function estDansSesClasses(Student $eleve): bool
    {
        $classes = PerimetreEnseignant::classes();

        if (! $classes) {
            return false;
        }

        return $eleve->enrollments()
            ->where('status', 'active')
            ->whereIn('class_id', $classes)
            ->exists();
    }
}
