<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chacun ne voit que ce qui le concerne.
 *
 * Un élève ne voit que ce qui le concerne.
 *
 * Les routes de gestion — liste des élèves, paiements, notes de toute une
 * classe — ne portent qu'un garde `auth` : sans ce filtre, un compte élève
 * y accédait comme un administrateur.
 *
 * La scolarité ne figure pas dans sa liste blanche : ce qui est dû, ce qui est
 * réglé et les reçus regardent le parent, qui les a sur son portail.
 */
class RestreindreLesEleves
{
    /** Ce à quoi un élève a droit, par nom de route. */
    private const AUTORISEES = [
        'dashboard',
        'mon-espace',
        'mon-espace.*',
        'logout',
        'login',
        'profile.*',
        // Son bulletin : le contrôleur vérifie qu'il s'agit bien de lui avant
        // de rendre quoi que ce soit.
        'grades.bulletin',
        'grades.bulletin.pdf',
    ];

    /**
     * Ce à quoi un parent a droit : son portail, et rien au-delà.
     *
     * Les données de ses enfants lui sont entières — bulletins, reçus — mais
     * le contrôleur vérifie à chaque fois qu'il s'agit bien des siens.
     */
    private const AUTORISEES_AUX_PARENTS = [
        'dashboard',
        'parent-portal.*',
        'logout',
        'login',
        'profile.*',
        'grades.bulletin',
        'grades.bulletin.pdf',
        'payments.receipt',
    ];

    /**
     * Ce qui ne concerne pas un enseignant : la caisse de l'établissement.
     *
     * Les écrans de gestion pédagogique lui restent ouverts, mais bornés à
     * son périmètre — voir `PerimetreEnseignant`.
     */
    private const INTERDITES_AUX_ENSEIGNANTS = [
        'payments.*',
        'fees.*',
        'enrollments.*',
        'parents.*',
        'statistics*',
    ];

    /**
     * La caisse de l'établissement, quel que soit le chemin pris pour y venir.
     *
     * Le censeur seconde le chef d'établissement sur la scolarité et la
     * discipline ; les frais et les encaissements ne relèvent pas de lui, et sa
     * fiche de compte l'annonce en toutes lettres. Le menu masquait déjà la
     * rubrique — mais un menu masqué n'est pas une porte fermée : l'adresse
     * tapée à la main répondait 200.
     */
    private const CAISSE = [
        'payments.*',
        'payment',
        'payment-*',
        'process-payment',
        'online-payment.*',
        'fees.*',
        'fees',
        'level-fee*',
        'class-fee*',
        'enrollment-fee*',
        'dashboard.export.financial',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $utilisateur = $request->user();

        if (! $utilisateur) {
            return $next($request);
        }

        $route = $request->route()?->getName();

        if ($utilisateur->role === 'student') {
            foreach (self::AUTORISEES as $motif) {
                if ($route && ($route === $motif || fnmatch($motif, $route))) {
                    return $next($request);
                }
            }

            abort(403, 'Cette page n’est pas accessible depuis un compte élève.');
        }

        if ($utilisateur->role === 'parent') {
            foreach (self::AUTORISEES_AUX_PARENTS as $motif) {
                if ($route && ($route === $motif || fnmatch($motif, $route))) {
                    return $next($request);
                }
            }

            abort(403, 'Cette page n’est pas accessible depuis un compte parent.');
        }

        if ($utilisateur->role === 'teacher' && $route) {
            foreach (self::INTERDITES_AUX_ENSEIGNANTS as $motif) {
                if ($route === $motif || fnmatch($motif, $route)) {
                    abort(403, 'Cette page n’est pas accessible depuis un compte enseignant.');
                }
            }
        }

        // Le catalogue des rôles dit qui a la caisse ; ce garde le fait tenir.
        if ($route && ! \App\Support\Roles::voitLesFinances($utilisateur->role)) {
            foreach (self::CAISSE as $motif) {
                if ($route === $motif || fnmatch($motif, $route)) {
                    abort(403, 'Les frais et les encaissements ne relèvent pas de ce rôle.');
                }
            }
        }

        return $next($request);
    }
}
