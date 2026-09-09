<?php

namespace App\Http\Middleware;

use App\Models\ParentModel;
use App\Models\SchoolSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un compte parent sans fiche ne doit pas tomber sur un « 404 ».
 *
 * Le portail parent part de la fiche : les enfants, leurs soldes, leurs
 * bulletins. Sans elle, le contrôleur répondait « 404 — cette page n'existe
 * pas », ce qui est faux et n'apprend rien : la page existe, c'est le
 * rattachement qui manque. Le parent n'avait alors aucun moyen de comprendre,
 * et le secrétariat aucun indice de ce qu'il fallait corriger.
 *
 * Cet écran le dit, et renvoie vers le secrétariat.
 */
class ParentAvecFiche
{
    public function handle(Request $request, Closure $next): Response
    {
        $utilisateur = $request->user();

        if (! $utilisateur || $utilisateur->role !== 'parent') {
            return $next($request);
        }

        $aUneFiche = ParentModel::withoutGlobalScopes()
            ->where('user_id', $utilisateur->id)
            ->exists();

        if ($aUneFiche) {
            return $next($request);
        }

        return response()->view('parent-portal.sans-fiche', [
            'utilisateur' => $utilisateur,
            'reglages' => SchoolSettings::getSettings(),
        ]);
    }
}
