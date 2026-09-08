@extends('layouts.app')

@section('titre', 'Sécurité')
@section('sous-titre', 'Configuration, comptes à privilèges et journaux applicatifs')

@section('actions-entete')
    <a href="{{ route('admin.logs.download') }}" class="bouton-secondaire">Télécharger les journaux</a>
    <a href="{{ route('admin.settings.index') }}" class="bouton-primaire">Paramètres généraux</a>
@endsection

@section('contenu')

@php
    $reglages = $securityInfo['security_settings'] ?? [];
    $chiffres = $securityInfo['security_stats'] ?? [];
    $journaux = collect($securityInfo['recent_logs'] ?? []);
    $fichiers = collect($securityInfo['logs_summary'] ?? []);

    $vrai = fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN);

    /*
     * Chaque reglage est juge : ce qui est attendu en production et ce qui
     * l'est reellement. Afficher « true / false » sans verdict n'aide pas.
     */
    $controles = [
        [
            'libelle' => 'Mode debug désactivé',
            'conforme' => ! $vrai($reglages['app_debug'] ?? false),
            'detail' => $vrai($reglages['app_debug'] ?? false)
                ? 'APP_DEBUG=true : les traces d’erreur exposent le code.'
                : 'Les traces d’erreur ne sont pas exposées.',
        ],
        [
            'libelle' => 'HTTPS',
            'conforme' => $vrai($reglages['https_enabled'] ?? false),
            'detail' => $vrai($reglages['https_enabled'] ?? false)
                ? 'Les échanges sont chiffrés.'
                : 'Connexion en clair — à activer avant mise en ligne.',
        ],
        [
            'libelle' => 'Protection CSRF',
            'conforme' => $vrai($reglages['csrf_protection'] ?? false),
            'detail' => 'Vérification des jetons sur les formulaires.',
        ],
        [
            'libelle' => 'Cookie de session sécurisé',
            'conforme' => $vrai($reglages['session_secure'] ?? false),
            'detail' => 'Le cookie n’est transmis qu’en HTTPS.',
        ],
        [
            'libelle' => 'Cookie inaccessible au JavaScript',
            'conforme' => $vrai($reglages['session_http_only'] ?? false),
            'detail' => 'HttpOnly : limite le vol de session par script.',
        ],
        [
            'libelle' => 'Politique SameSite',
            'conforme' => in_array($reglages['session_same_site'] ?? null, ['lax', 'strict'], true),
            'detail' => 'Valeur : '.($reglages['session_same_site'] ?? 'non définie'),
        ],
    ];

    $anomalies = collect($controles)->reject->conforme->count();

    // Une ligne de journal commence par « [date] canal.NIVEAU: ».
    $niveauDuJournal = function ($ligne) {
        if (preg_match('/\.(EMERGENCY|ALERT|CRITICAL|ERROR)\b/i', $ligne)) return ['Erreur', 'rose'];
        if (preg_match('/\.(WARNING|NOTICE)\b/i', $ligne)) return ['Avertissement', 'amber'];
        if (preg_match('/\.(INFO|DEBUG)\b/i', $ligne)) return ['Information', 'sky'];

        return ['Trace', 'slate'];
    };
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Contrôles conformes"
                       :valeur="count($controles) - $anomalies.' / '.count($controles)"
                       :detail="$anomalies > 0 ? $anomalies.' point(s) à corriger' : 'Configuration conforme'"
                       :couleur="$anomalies > 0 ? 'rose' : 'emerald'"/>
        <x-statistique libelle="Sessions actives" :valeur="$chiffres['active_sessions'] ?? 0"
                       detail="Connexions en cours" couleur="ogar"/>
        <x-statistique libelle="Comptes à privilèges" :valeur="$chiffres['admin_users_count'] ?? 0"
                       detail="Admin et superadmin" couleur="violet"/>
        <x-statistique libelle="Comptes désactivés" :valeur="$chiffres['inactive_users_count'] ?? 0"
                       detail="Sans accès" couleur="amber"/>
    </div>

    <div class="mt-6 grid items-start gap-4 lg:grid-cols-3">

        {{-- ------------------------------------------------------------
             Les contrôles de configuration
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden lg:col-span-2">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Configuration</h2>
                <span class="text-xs text-gris-400">{{ count($controles) }} contrôle(s)</span>
            </div>

            <div class="divide-y divide-gris-100">
                @foreach ($controles as $controle)
                    <div class="flex items-start gap-3 px-5 py-3">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full
                                     {{ $controle['conforme'] ? 'bg-emerald-100 text-emerald-700' : 'bg-corail-100 text-corail-700' }}">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                @if ($controle['conforme'])
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                @else
                                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                                @endif
                            </svg>
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gris-800">{{ $controle['libelle'] }}</p>
                            <p class="text-[11px] text-gris-500">{{ $controle['detail'] }}</p>
                        </div>

                        <x-puce :couleur="$controle['conforme'] ? 'emerald' : 'rose'">
                            {{ $controle['conforme'] ? 'Conforme' : 'À corriger' }}
                        </x-puce>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Fichiers de journaux
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Fichiers de journaux</h2>
                <span class="text-xs text-gris-400">{{ $fichiers->count() }}</span>
            </div>

            <div class="divide-y divide-gris-100">
                @forelse ($fichiers as $fichier)
                    <div class="px-5 py-2.5">
                        <p class="truncate font-mono text-[11px] font-medium text-gris-800">{{ $fichier['name'] ?? '—' }}</p>
                        <p class="text-[11px] text-gris-400">
                            {{ $fichier['size'] ?? '—' }} · {{ $fichier['lines'] ?? 0 }} ligne(s)
                            @if ($fichier['modified'] ?? null) · {{ $fichier['modified'] }} @endif
                        </p>
                    </div>
                @empty
                    <p class="p-5 text-sm text-gris-400">Aucun fichier de journal.</p>
                @endforelse
            </div>

            <div class="border-t border-gris-100 p-4">
                <x-confirmation :action="route('admin.logs.clear')"
                                methode="POST"
                                titre="Vider les journaux ?"
                                message="Le contenu des fichiers de journaux sera effacé. Téléchargez-les d’abord si vous en avez besoin."
                                confirmer="Vider les journaux"
                                bouton="bouton-danger w-full justify-center">
                    Vider les journaux
                </x-confirmation>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Les vingt dernières lignes
         ---------------------------------------------------------------- --}}
    <div class="carte mt-4 overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Journal applicatif</h2>
                <p class="mt-0.5 text-xs text-gris-400">Les vingt dernières lignes, de la plus récente à la plus ancienne.</p>
            </div>
            <a href="{{ route('admin.logs.download') }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                Télécharger
            </a>
        </div>

        <div class="divide-y divide-gris-100">
            @forelse ($journaux as $ligne)
                @php([$niveau, $teinte] = $niveauDuJournal($ligne))
                <div class="flex items-start gap-3 px-5 py-2">
                    <x-puce :couleur="$teinte" class="mt-0.5 shrink-0">{{ $niveau }}</x-puce>
                    <p class="min-w-0 flex-1 break-all font-mono text-[11px] leading-relaxed text-gris-600">
                        {{ \Illuminate\Support\Str::limit($ligne, 400) }}
                    </p>
                </div>
            @empty
                <p class="p-5 text-sm text-gris-400">Le journal est vide.</p>
            @endforelse
        </div>
    </div>

@endsection
