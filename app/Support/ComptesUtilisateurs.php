<?php

namespace App\Support;

use App\Models\ParentModel;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * L'ouverture des comptes, pour les trois profils qui en reçoivent un.
 *
 * Chaque création — parent, enseignant, élève — ouvre un compte et engendre
 * son mot de passe initial. Ce mot de passe est **renvoyé en clair**, une
 * seule fois : il doit être remis à la personne, et l'écran qui suit la
 * création est le seul endroit où on peut encore le lire.
 *
 * Ce qui existait avant : le parent recevait `téléphone + 1234` — devinable,
 * puisque le numéro figure sur sa fiche — annoncé dans un message qui
 * disparaissait au premier changement de page ; l'enseignant n'avait aucun
 * compte ; l'élève n'en recevait un qu'au lycée, par une commande lancée à la
 * main.
 */
class ComptesUtilisateurs
{
    /**
     * L'alphabet des mots de passe engendrés.
     *
     * Ni O ni 0, ni I ni l ni 1 : ces mots de passe se lisent sur un papier
     * qu'on tend à quelqu'un, et se retapent de mémoire. Une confusion de
     * glyphe coûte un appel au secrétariat.
     */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const LONGUEUR = 8;

    public static function motDePasseInitial(): string
    {
        $alphabet = self::ALPHABET;
        $taille = strlen($alphabet);
        $mot = '';

        for ($i = 0; $i < self::LONGUEUR; $i++) {
            $mot .= $alphabet[random_int(0, $taille - 1)];
        }

        return $mot;
    }

    /**
     * Un numéro réduit à ses chiffres, indicatif gabonais retiré.
     *
     * Sans cela « 06 12 34 56 78 » et « +241 06 12 34 56 78 » désigneraient
     * deux abonnés différents, et l'un des deux ne pourrait pas se connecter.
     */
    public static function normaliserLeNumero(?string $numero): ?string
    {
        $chiffres = preg_replace('/\D+/', '', (string) $numero);

        if ($chiffres === '') {
            return null;
        }

        if (str_starts_with($chiffres, '241') && strlen($chiffres) > 9) {
            $chiffres = substr($chiffres, 3);
        }

        return strlen($chiffres) >= 6 ? $chiffres : null;
    }

    /**
     * Le numéro est-il déjà pris par un autre compte ?
     *
     * Deux parents partagent parfois un téléphone. Le second ne peut pas s'en
     * servir pour entrer : il lui restera son matricule, et son courriel s'il
     * en a un.
     */
    private static function numeroDisponible(?string $numero, ?int $sauf = null): ?string
    {
        if ($numero === null) {
            return null;
        }

        $pris = User::where('telephone', $numero)
            ->when($sauf, fn ($q) => $q->where('id', '!=', $sauf))
            ->exists();

        return $pris ? null : $numero;
    }

    /* ==================================================================
       Parent
       ================================================================== */

    /**
     * Ouvre le compte d'un parent. Renvoie le mot de passe initial, ou null
     * si le parent avait déjà un compte.
     */
    public static function ouvrirPourParent(ParentModel $parent): ?string
    {
        if ($parent->user_id && User::whereKey($parent->user_id)->exists()) {
            return null;
        }

        $matricule = 'PAR'.str_pad((string) $parent->id, 6, '0', STR_PAD_LEFT);
        $motDePasse = self::motDePasseInitial();

        $compte = User::create([
            'name' => trim($parent->first_name.' '.$parent->last_name),
            'email' => $parent->email ?: null,
            'telephone' => self::numeroDisponible(self::normaliserLeNumero($parent->phone)),
            'matricule' => $matricule,
            'password' => Hash::make($motDePasse),
            'role' => 'parent',
            'school_id' => $parent->school_id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $parent->forceFill(['user_id' => $compte->id])->save();

        return $motDePasse;
    }

    /* ==================================================================
       Enseignant
       ================================================================== */

    public static function ouvrirPourEnseignant(Teacher $enseignant): ?string
    {
        if ($enseignant->user_id && User::whereKey($enseignant->user_id)->exists()) {
            return null;
        }

        $matricule = $enseignant->employee_id;

        if (blank($matricule)) {
            return null;
        }

        $motDePasse = self::motDePasseInitial();

        $compte = User::create([
            'name' => trim($enseignant->first_name.' '.$enseignant->last_name),
            'email' => $enseignant->email ?: null,
            'telephone' => self::numeroDisponible(self::normaliserLeNumero($enseignant->phone)),
            'matricule' => $matricule,
            'password' => Hash::make($motDePasse),
            'role' => 'teacher',
            'school_id' => $enseignant->school_id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $enseignant->forceFill(['user_id' => $compte->id])->save();

        return $motDePasse;
    }

    /* ==================================================================
       Élève
       ================================================================== */

    /**
     * Ouvre le compte d'un élève du lycée.
     *
     * `$motDePasse` permet d'imposer une valeur connue — le peuplement de
     * démonstration s'en sert pour que l'accès rapide de la page de connexion
     * ait un compte utilisable. Toute création réelle laisse engendrer.
     */
    public static function ouvrirPourEleve(Student $eleve, ?string $motDePasse = null): ?string
    {
        if (! ComptesEleves::estAuLycee($eleve)) {
            return null;
        }

        if ($eleve->user_id && User::whereKey($eleve->user_id)->exists()) {
            return null;
        }

        $matricule = $eleve->student_id;

        if (blank($matricule)) {
            return null;
        }

        if (User::where('matricule', $matricule)->exists()) {
            return null;
        }

        $motDePasse ??= self::motDePasseInitial();

        $compte = User::create([
            'name' => trim($eleve->first_name.' '.$eleve->last_name),
            'email' => null,
            'telephone' => self::numeroDisponible(self::normaliserLeNumero($eleve->phone)),
            'matricule' => $matricule,
            'password' => Hash::make($motDePasse),
            'role' => 'student',
            'school_id' => $eleve->school_id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $eleve->forceFill(['user_id' => $compte->id])->save();

        return $motDePasse;
    }

    /**
     * Ce qu'il faut annoncer à l'écran après une création.
     *
     * Le mot de passe ne se retrouve nulle part ensuite : il n'est pas stocké
     * en clair. C'est ici, et une seule fois.
     */
    public static function aRemettre(string $identifiant, string $motDePasse): array
    {
        return ['identifiant' => $identifiant, 'mot_de_passe' => $motDePasse];
    }
}
