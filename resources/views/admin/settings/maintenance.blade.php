@extends('layouts.app')

@section('titre', 'Maintenance')
@section('sous-titre', 'Caches, optimisation et sauvegarde de la base')

@section('actions-entete')
    <a href="{{ route('admin.system-info') }}" class="bouton-secondaire">Informations système</a>
    <a href="{{ route('admin.settings.index') }}" class="bouton-primaire">Paramètres généraux</a>
@endsection

@section('contenu')

@php
    $cacheChaud = (bool) ($maintenanceInfo['cache_status'] ?? false);
    $echecs = (int) ($maintenanceInfo['failed_jobs'] ?? 0);
    $sauvegarde = $maintenanceInfo['last_backup'] ?? null;

    /*
     * Chaque operation est decrite pour ce qu'elle fait vraiment : « vider le
     * cache » et « optimiser » ont des effets opposes, les confondre coute une
     * mise en production.
     */
    $operations = [
        [
            'titre' => 'Vider les caches',
            'texte' => 'Efface la configuration, les routes, les vues et le cache applicatif compilés. À faire après une modification de configuration.',
            'route' => 'admin.maintenance.clear-cache',
            'bouton' => 'Vider les caches',
            'confirmation' => 'Les caches de configuration, de routes et de vues seront effacés. L’application sera brièvement plus lente, le temps de les reconstruire.',
            'ton' => 'primaire',
        ],
        [
            'titre' => 'Optimiser l’application',
            'texte' => 'Recompile et met en cache la configuration, les routes et les vues. À faire une fois la configuration stabilisée.',
            'route' => 'admin.maintenance.optimize',
            'bouton' => 'Optimiser',
            'confirmation' => 'La configuration, les routes et les vues seront recompilées et mises en cache.',
            'ton' => 'primaire',
        ],
        [
            'titre' => 'Sauvegarder la base',
            'texte' => 'Écrit une copie de la base de données sur le serveur. À lancer avant toute opération lourde.',
            'route' => 'admin.maintenance.backup',
            'bouton' => 'Lancer la sauvegarde',
            'confirmation' => 'Une copie complète de la base de données va être écrite sur le serveur. L’opération peut prendre un moment.',
            'ton' => 'primaire',
        ],
    ];
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Dernière sauvegarde"
                       :valeur="$sauvegarde ?: 'Aucune'"
                       :detail="$sauvegarde ? 'Copie disponible sur le serveur' : 'Base jamais sauvegardée'"
                       :couleur="$sauvegarde ? 'emerald' : 'rose'"/>
        <x-statistique libelle="Journaux" :valeur="$maintenanceInfo['logs_size'] ?? '—'"
                       detail="storage/logs" couleur="ogar"/>
        <x-statistique libelle="Tâches en échec" :valeur="$echecs"
                       :detail="$echecs > 0 ? 'À examiner' : 'File d’attente saine'"
                       :couleur="$echecs > 0 ? 'rose' : 'violet'"/>
        <x-statistique libelle="Cache applicatif" :valeur="$cacheChaud ? 'Actif' : 'Vide'"
                       :detail="$cacheChaud ? 'Réponses accélérées' : 'Sera reconstruit à la demande'"
                       :couleur="$cacheChaud ? 'emerald' : 'amber'"/>
    </div>

    @if ($echecs > 0)
        <div class="mt-4 flex items-start gap-3 rounded-xl border border-soleil-200 bg-soleil-50 px-4 py-3 text-sm text-soleil-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span>
                <strong>{{ $echecs }} tâche(s) en échec</strong> dans la file d’attente.
                Elles ne seront pas rejouées d’elles-mêmes.
            </span>
        </div>
    @endif

    @unless ($sauvegarde)
        <div class="mt-4 flex items-start gap-3 rounded-xl border border-corail-200 bg-corail-50 px-4 py-3 text-sm text-corail-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span>
                <strong>Aucune sauvegarde n’a jamais été faite.</strong>
                Lancez-en une avant toute opération de maintenance.
            </span>
        </div>
    @endunless

    {{-- ----------------------------------------------------------------
         Les opérations
         ---------------------------------------------------------------- --}}
    <div class="mt-4 grid items-start gap-4 md:grid-cols-3">
        @foreach ($operations as $operation)
            <div class="carte flex h-full flex-col p-5">
                <h2 class="text-sm font-semibold text-gris-900">{{ $operation['titre'] }}</h2>
                <p class="mt-1 flex-1 text-xs leading-relaxed text-gris-500">{{ $operation['texte'] }}</p>

                <div class="mt-4">
                    <x-confirmation :action="route($operation['route'])"
                                    methode="POST"
                                    :titre="$operation['titre'].' ?'"
                                    :message="$operation['confirmation']"
                                    :confirmer="$operation['bouton']"
                                    :ton="$operation['ton']"
                                    bouton="bouton-primaire w-full justify-center">
                        {{ $operation['bouton'] }}
                    </x-confirmation>
                </div>
            </div>
        @endforeach
    </div>

    <div class="carte mt-4 p-5">
        <h2 class="mb-2 text-sm font-semibold text-gris-900">Bon à savoir</h2>
        <ul class="space-y-1.5 text-xs leading-relaxed text-gris-600">
            <li>· <strong>Vider les caches</strong> et <strong>optimiser</strong> font l’inverse l’un de l’autre : videz après avoir modifié la configuration, optimisez une fois qu’elle est stable.</li>
            <li>· L’optimisation met les routes en cache : toute route ajoutée ensuite reste ignorée jusqu’au prochain vidage.</li>
            <li>· Les journaux se consultent et se téléchargent depuis <a href="{{ route('admin.security') }}" class="font-semibold text-ogar-600 hover:underline">Sécurité</a>.</li>
        </ul>
    </div>

@endsection
