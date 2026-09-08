@extends('layouts.app')

@section('titre', 'Informations système')
@section('sous-titre', 'Environnement d’exécution — '.($systemInfo['laravel']['environment'] ?? '').' · PHP '.($systemInfo['php']['version'] ?? ''))

@section('actions-entete')
    <a href="{{ route('admin.maintenance') }}" class="bouton-secondaire">Maintenance</a>
    <a href="{{ route('admin.settings.index') }}" class="bouton-primaire">Paramètres généraux</a>
@endsection

@section('contenu')

@php
    $php = $systemInfo['php'] ?? [];
    $serveur = $systemInfo['server'] ?? [];
    $laravel = $systemInfo['laravel'] ?? [];
    $base = $systemInfo['database'] ?? [];
    $perf = $systemInfo['performance'] ?? [];

    $extensions = collect($php['extensions'] ?? [])->sort()->values();

    // Le mode debug en production est la seule anomalie que cette page peut
    // signaler d'elle-meme : autant la rendre visible.
    $debugActif = filter_var($laravel['debug_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $enProduction = ($laravel['environment'] ?? '') === 'production';

    $bloc = function (string $titre, array $lignes) {
        return ['titre' => $titre, 'lignes' => $lignes];
    };

    $blocs = [
        $bloc('PHP', [
            'Version' => $php['version'] ?? '—',
            'Mémoire allouée' => $php['memory_limit'] ?? '—',
            'Temps d’exécution max.' => ($php['max_execution_time'] ?? '—').' s',
            'Téléversement max.' => $php['upload_max_filesize'] ?? '—',
            'Taille de requête max.' => $php['post_max_size'] ?? '—',
        ]),
        $bloc('Serveur', [
            'Logiciel' => $serveur['software'] ?? '—',
            'Système' => $serveur['os'] ?? '—',
            'Hôte' => $serveur['hostname'] ?? '—',
            'Racine' => $serveur['document_root'] ?? '—',
        ]),
        $bloc('Application', [
            'Laravel' => $laravel['version'] ?? '—',
            'Environnement' => $laravel['environment'] ?? '—',
            'Fuseau horaire' => $laravel['timezone'] ?? '—',
            'Langue' => $laravel['locale'] ?? '—',
            'Cache' => $laravel['cache_driver'] ?? '—',
            'Sessions' => $laravel['session_driver'] ?? '—',
            'Files d’attente' => $laravel['queue_driver'] ?? '—',
        ]),
        $bloc('Base de données', [
            'Connexion' => $base['connection'] ?? '—',
            'Hôte' => $base['host'] ?? '—',
            'Base' => $base['database'] ?? '—',
            'Version' => $base['version'] ?? '—',
            'Taille' => $base['size'] ?? '—',
            'Tables' => $base['tables_count'] ?? '—',
        ]),
    ];
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Base de données" :valeur="$base['size'] ?? '—'"
                       :detail="($base['tables_count'] ?? 0).' table(s)'" couleur="ogar"/>
        <x-statistique libelle="Stockage" :valeur="$perf['storage_used'] ?? '—'"
                       :detail="'Cache : '.($perf['cache_size'] ?? '—')" couleur="emerald"/>
        <x-statistique libelle="Mémoire utilisée" :valeur="$perf['memory_usage'] ?? '—'"
                       :detail="'Pic : '.($perf['memory_peak'] ?? '—')" couleur="violet"/>
        <x-statistique libelle="Journaux" :valeur="$perf['logs_size'] ?? '—'"
                       detail="storage/logs" couleur="amber"/>
    </div>

    @if ($debugActif && $enProduction)
        <div class="mt-4 flex items-start gap-3 rounded-xl border border-corail-200 bg-corail-50 px-4 py-3 text-sm text-corail-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span>
                <strong>Le mode debug est actif en production.</strong>
                Les traces d’erreur exposent le code et la configuration : passez <code>APP_DEBUG</code> à <code>false</code>.
            </span>
        </div>
    @endif

    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
        @foreach ($blocs as $carte)
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">{{ $carte['titre'] }}</h2>
                </div>

                <dl class="divide-y divide-gris-100">
                    @foreach ($carte['lignes'] as $libelle => $valeur)
                        <div class="flex items-baseline justify-between gap-4 px-5 py-2">
                            <dt class="shrink-0 text-sm text-gris-500">{{ $libelle }}</dt>
                            <dd class="truncate text-right text-sm font-medium text-gris-800">{{ $valeur }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endforeach
    </div>

    <div class="carte mt-4 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Extensions PHP chargées</h2>
            <span class="text-xs text-gris-400">{{ $extensions->count() }}</span>
        </div>

        <div class="flex flex-wrap gap-1.5 p-5">
            @forelse ($extensions as $extension)
                <span class="rounded border border-gris-200 bg-gris-50 px-2 py-0.5 font-mono text-[11px] text-gris-600">
                    {{ $extension }}
                </span>
            @empty
                <p class="text-sm text-gris-400">Liste des extensions indisponible.</p>
            @endforelse
        </div>
    </div>

@endsection
