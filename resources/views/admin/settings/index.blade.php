@extends('layouts.app')

@section('titre', 'Paramètres généraux')
@section('sous-titre', 'Administration de l’application — '.($systemInfo['laravel_version'] ?? '').' · PHP '.($systemInfo['php_version'] ?? ''))

@section('actions-entete')
    @if (auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.schools.index') }}" class="bouton-primaire">Établissements</a>
    @else
        <a href="{{ route('admin.school-settings.index') }}" class="bouton-primaire">Paramètres de l’établissement</a>
    @endif
@endsection

@section('contenu')

@php
    $nombre = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $superadmin = auth()->user()->isSuperAdmin();

    // Les pages réservées au superadmin sont annoncées mais désactivées pour
    // les autres : mieux vaut le dire que d'aboutir sur un 403.
    // L'administration d'une école appartient à son administrateur ; le
    // superadmin ne la voit qu'après s'être placé dans un établissement.
    $dansUnEtablissement = ! $superadmin || \App\Support\EcoleCourante::id() !== null;

    $rubriques = array_values(array_filter([
        $superadmin ? [
            'titre' => 'Établissements',
            'texte' => 'Créer une école, ouvrir ou fermer son accès, choisir celle dans laquelle travailler.',
            'route' => 'admin.schools.index',
            'reserve' => true,
        ] : null,
        $dansUnEtablissement ? [
            'titre' => 'Paramètres de l’établissement',
            'texte' => 'Nom, contact, logo, sceau, direction — ce qui figure sur les documents officiels.',
            'route' => 'admin.school-settings.index',
            'reserve' => false,
        ] : null,
        $dansUnEtablissement ? [
            'titre' => 'Comptes utilisateurs',
            'texte' => 'Créer, désactiver et attribuer les rôles des personnes qui accèdent à l’application.',
            'route' => 'admin.users.index',
            'reserve' => false,
        ] : null,
        [
            'titre' => 'Informations système',
            'texte' => 'Versions, extensions PHP, base de données, occupation disque et mémoire.',
            'route' => 'admin.system-info',
            'reserve' => false,
        ],
        [
            'titre' => 'Sécurité',
            'texte' => 'Configuration de session, comptes à privilèges et journaux applicatifs.',
            'route' => 'admin.security',
            'reserve' => false,
        ],
        [
            'titre' => 'Maintenance',
            'texte' => 'Vider les caches, optimiser l’application, sauvegarder la base.',
            'route' => 'admin.maintenance',
            'reserve' => false,
        ],
    ]));
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Comptes utilisateurs" :valeur="$nombre($stats['users'])"
                       :detail="$stats['active_users'].' actif(s)'" couleur="ogar"/>
        <x-statistique libelle="Élèves" :valeur="$nombre($stats['students'])"
                       :detail="$stats['classes'].' classe(s)'" couleur="emerald"/>
        <x-statistique libelle="Enseignants" :valeur="$nombre($stats['teachers'])"
                       :detail="$stats['subjects'].' matière(s)'" couleur="violet"/>
        <x-statistique libelle="Base de données" :valeur="$systemInfo['database_size'] ?? '—'"
                       :detail="'Stockage : '.($systemInfo['storage_used'] ?? '—')" couleur="amber"/>
    </div>

    {{-- ----------------------------------------------------------------
         Les rubriques d'administration
         ---------------------------------------------------------------- --}}
    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($rubriques as $rubrique)
            @php($accessible = ! $rubrique['reserve'] || $superadmin)

            @if ($accessible)
                <a href="{{ route($rubrique['route']) }}"
                   class="carte flex flex-col p-5 transition-colors hover:border-ogar-300 hover:bg-gris-50">
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="text-sm font-semibold text-gris-900">{{ $rubrique['titre'] }}</h2>
                        @if ($rubrique['reserve'])
                            <x-puce couleur="violet">Superadmin</x-puce>
                        @endif
                    </div>
                    <p class="mt-1 text-xs leading-relaxed text-gris-500">{{ $rubrique['texte'] }}</p>
                </a>
            @else
                <div class="carte flex cursor-not-allowed flex-col p-5 opacity-60">
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="text-sm font-semibold text-gris-900">{{ $rubrique['titre'] }}</h2>
                        <x-puce couleur="slate">Superadmin</x-puce>
                    </div>
                    <p class="mt-1 text-xs leading-relaxed text-gris-500">{{ $rubrique['texte'] }}</p>
                    <p class="mt-2 text-[11px] text-corail-600">Réservé au super administrateur.</p>
                </div>
            @endif
        @endforeach
    </div>

    {{-- ----------------------------------------------------------------
         Environnement et activité récente
         ---------------------------------------------------------------- --}}
    <div class="mt-4 grid items-start gap-4 lg:grid-cols-3">
        <div class="carte p-5">
            <h2 class="mb-3 text-sm font-semibold text-gris-900">Environnement</h2>

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Laravel</dt>
                    <dd class="font-medium text-gris-800">{{ $systemInfo['laravel_version'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">PHP</dt>
                    <dd class="font-medium text-gris-800">{{ $systemInfo['php_version'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Base de données</dt>
                    <dd class="font-medium text-gris-800">{{ $systemInfo['database_size'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Stockage utilisé</dt>
                    <dd class="font-medium text-gris-800">{{ $systemInfo['storage_used'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Cache</dt>
                    <dd class="font-medium text-gris-800">{{ $systemInfo['cache_size'] ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Derniers comptes créés</h2>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">Tous</a>
            </div>

            <div class="divide-y divide-gris-100">
                @forelse ($recentActivity['recent_users'] ?? [] as $utilisateur)
                    <div class="flex items-center gap-3 px-5 py-2.5">
                        <x-avatar :nom="$utilisateur->name" class="h-8 w-8 shrink-0 text-[10px]"/>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.users.show', $utilisateur) }}"
                               class="block truncate text-sm font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                {{ $utilisateur->name }}
                            </a>
                            <div class="truncate text-[11px] text-gris-400">{{ $utilisateur->email }}</div>
                        </div>
                        <x-puce couleur="slate">{{ $utilisateur->role }}</x-puce>
                    </div>
                @empty
                    <p class="p-5 text-sm text-gris-400">Aucun compte enregistré.</p>
                @endforelse
            </div>
        </div>

        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Derniers élèves inscrits</h2>
                <a href="{{ route('students.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">Tous</a>
            </div>

            <div class="divide-y divide-gris-100">
                @forelse ($recentActivity['recent_students'] ?? [] as $eleve)
                    <div class="flex items-center gap-3 px-5 py-2.5">
                        <x-avatar :nom="$eleve->first_name.' '.$eleve->last_name" :photo="$eleve->photo ?? null" class="h-8 w-8 shrink-0 text-[10px]"/>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('students.show', $eleve->id) }}"
                               class="block truncate text-sm font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                {{ $eleve->first_name }} {{ $eleve->last_name }}
                            </a>
                            <div class="truncate font-mono text-[11px] text-gris-400">{{ $eleve->student_id }}</div>
                        </div>
                    </div>
                @empty
                    <p class="p-5 text-sm text-gris-400">Aucun élève enregistré.</p>
                @endforelse
            </div>
        </div>
    </div>

@endsection
