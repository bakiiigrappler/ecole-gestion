<?php

namespace App\Support;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Comptes de connexion des élèves.
 *
 * Décidé avec l'utilisateur : **seuls les élèves du lycée** en ont un, et ils
 * se connectent avec leur **matricule**. Le mot de passe initial est le
 * matricule lui-même — l'élève n'a donc qu'une chose à retenir — et reste
 * modifiable ensuite.
 */
class ComptesEleves
{
    /**
     * Ouvre le compte d'un élève, ou le retrouve s'il existe déjà.
     *
     * Renvoie null si l'élève n'est pas au lycée : les autres cycles n'ont
     * pas de compte.
     */
    public static function ouvrirPour(Student $eleve): ?User
    {
        if (! self::estAuLycee($eleve)) {
            return null;
        }

        if ($eleve->user_id && $compte = User::find($eleve->user_id)) {
            return $compte;
        }

        $matricule = $eleve->student_id;

        if (blank($matricule)) {
            return null;
        }

        // Le matricule est l'identifiant : deux élèves ne peuvent pas le
        // partager, la colonne l'impose déjà côté élèves.
        $compte = User::where('matricule', $matricule)->first() ?? User::create([
            'name' => trim($eleve->first_name.' '.$eleve->last_name),
            'email' => null,
            'matricule' => $matricule,
            'password' => Hash::make($matricule),
            'role' => 'student',
            'school_id' => $eleve->school_id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $eleve->forceFill(['user_id' => $compte->id])->save();

        return $compte;
    }

    /**
     * Ouvre les comptes manquants pour tout un établissement.
     *
     * Renvoie le nombre de comptes créés.
     */
    public static function ouvrirPourLeLycee(?int $ecoleId = null): int
    {
        $eleves = Student::query()
            ->when($ecoleId, fn ($q) => $q->tousEtablissements()->where('school_id', $ecoleId))
            ->whereNull('user_id')
            ->whereHas('enrollments', fn ($q) => $q
                ->where('status', 'active')
                ->whereHas('schoolClass.level', fn ($r) => $r->where('cycle', 'lycee')))
            ->get();

        $ouverts = 0;

        foreach ($eleves as $eleve) {
            if (self::ouvrirPour($eleve)) {
                $ouverts++;
            }
        }

        return $ouverts;
    }

    /**
     * L'élève suit-il une classe de lycée cette année ?
     */
    public static function estAuLycee(Student $eleve): bool
    {
        return $eleve->enrollments()
            ->where('status', 'active')
            ->whereHas('schoolClass.level', fn ($q) => $q->where('cycle', 'lycee'))
            ->exists();
    }
}
