@extends('layouts.app')

@section('titre', $user->name)
@section('sous-titre', \App\Support\Roles::libelle($user->role).' · compte ouvert le '.optional($user->created_at)->format('d/m/Y'))

@section('actions-entete')
    <a href="{{ route('admin.users.index') }}" class="bouton-secondaire">Tous les comptes</a>
    @if (! $user->isSuperAdmin() || auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.users.edit', $user) }}" class="bouton-primaire">Modifier</a>
    @endif
@endsection

@section('contenu')

@php
    // Classes ecrites en entier : Tailwind ne compile pas une teinte interpolee.
    $puceRole = [
        'superadmin' => 'rose', 'admin' => 'violet', 'directeur' => 'indigo',
        'proviseur' => 'indigo', 'censeur' => 'sky', 'secretary' => 'sky',
        'teacher' => 'emerald', 'parent' => 'amber', 'student' => 'slate',
    ];

    $acces = \App\Support\Roles::acces($user->role);
    $soiMeme = $user->id === auth()->id();
    $modifiable = ! $user->isSuperAdmin() || auth()->user()->isSuperAdmin();

    // Ces trois profils se gerent depuis leur propre module : la fiche y porte
    // les classes, les enfants ou l'inscription que ce seul compte ignore.
    $moduleDedie = match ($user->role) {
        'teacher' => ['libelle' => 'Enseignants', 'url' => route('teachers.index')],
        'parent' => ['libelle' => 'Parents', 'url' => route('parents.index')],
        'student' => ['libelle' => 'Élèves', 'url' => route('students.index')],
        default => null,
    };
@endphp

{{-- Le mot de passe qui vient d'etre engendre : lisible ici, et nulle part ailleurs. --}}
<x-compte-ouvert/>

<div class="grid gap-6 lg:grid-cols-3">

    <div class="space-y-4 lg:col-span-2">

        {{-- ------------------------------------------------------------
             Identité
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="flex flex-wrap items-center gap-4 p-5">
                <x-avatar :nom="$user->name" class="h-16 w-16 shrink-0 text-lg"/>

                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-lg font-semibold text-gris-900">{{ $user->name }}</h2>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        <x-puce :couleur="$puceRole[$user->role] ?? 'slate'">
                            {{ \App\Support\Roles::libelle($user->role) }}
                        </x-puce>
                        <x-puce :couleur="$user->is_active ? 'emerald' : 'slate'">
                            {{ $user->is_active ? 'Actif' : 'Désactivé' }}
                        </x-puce>
                        @if ($soiMeme)
                            <x-puce couleur="ogar">C’est vous</x-puce>
                        @endif
                    </div>
                </div>
            </div>

            <dl class="grid gap-px border-t border-gris-100 bg-gris-100 sm:grid-cols-2">
                <div class="bg-white px-5 py-3">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Matricule</dt>
                    <dd class="mt-0.5 font-mono text-sm font-semibold text-gris-900">{{ $user->matricule ?: '—' }}</dd>
                </div>
                <div class="bg-white px-5 py-3">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Adresse e-mail</dt>
                    <dd class="mt-0.5 truncate text-sm text-gris-800">
                        @if ($user->email)
                            <a href="mailto:{{ $user->email }}" class="hover:text-ogar-700 hover:underline">{{ $user->email }}</a>
                        @else
                            <span class="text-gris-400">Aucune</span>
                        @endif
                    </dd>
                </div>
                <div class="bg-white px-5 py-3">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Téléphone</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-gris-800">
                        {{ $user->telephone ?: '—' }}
                    </dd>
                </div>
                <div class="bg-white px-5 py-3">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Dernière connexion</dt>
                    <dd class="mt-0.5 text-sm text-gris-800">
                        @if ($user->last_login_at)
                            {{ $user->last_login_at->format('d/m/Y à H:i') }}
                        @else
                            <span class="text-gris-400">Jamais connecté</span>
                        @endif
                    </dd>
                </div>
            </dl>

            <p class="border-t border-gris-100 px-5 py-3 text-[11px] text-gris-500">
                Se connecte avec son matricule{{ $user->email ? ', son adresse e-mail' : '' }}{{ $user->telephone ? ' ou son numéro de téléphone' : '' }}.
            </p>
        </div>

        {{-- ------------------------------------------------------------
             Le mot de passe : ce qu'on peut encore en faire
             ------------------------------------------------------------ --}}
        @if ($modifiable)
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <div>
                        <h2 class="text-sm font-semibold text-gris-900">Mot de passe</h2>
                        <p class="mt-0.5 text-xs text-gris-400">
                            À engendrer de nouveau quand la personne l’a perdu.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                    <p class="max-w-lg text-sm leading-relaxed text-gris-600">
                        Le mot de passe n’est pas conservé en clair : il n’est lisible qu’une fois,
                        sur l’écran qui suit sa création. Le bouton ci-contre en engendre un nouveau
                        et l’affiche ici même, prêt à être copié et remis à l’intéressé —
                        <strong class="text-gris-900">l’ancien cesse aussitôt de fonctionner</strong>.
                    </p>

                    <x-confirmation :action="route('admin.users.mot-de-passe', $user)"
                                    methode="POST"
                                    titre="Engendrer un nouveau mot de passe ?"
                                    :message="$user->name.' ne pourra plus entrer avec son mot de passe actuel. Le nouveau s’affichera une seule fois, à vous de le lui remettre.'"
                                    confirmer="Engendrer"
                                    ton="primaire"
                                    bouton="bouton-secondaire shrink-0">
                        Engendrer un nouveau mot de passe
                    </x-confirmation>
                </div>
            </div>
        @endif

        {{-- ------------------------------------------------------------
             Ce que le rôle ouvre
             ------------------------------------------------------------ --}}
        @if ($acces)
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <div>
                        <h2 class="text-sm font-semibold text-gris-900">Droits d’accès</h2>
                        <p class="mt-0.5 text-xs text-gris-400">
                            {{ \App\Support\Roles::detail($user->role) }}
                        </p>
                    </div>
                </div>

                <ul class="divide-y divide-gris-100">
                    @foreach ($acces as $droit)
                        @php $refus = str_starts_with($droit, 'Aucun'); @endphp
                        <li class="flex items-start gap-3 px-5 py-3 text-sm text-gris-700">
                            @if ($refus)
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-corail-500" fill="none" stroke="currentColor"
                                     stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @else
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor"
                                     stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            @endif
                            <span class="{{ $refus ? 'text-gris-500' : '' }}">{{ $droit }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- ------------------------------------------------------------
         Colonne latérale
         ------------------------------------------------------------ --}}
    <div class="space-y-4">

        @if ($moduleDedie)
            <div class="carte border-ogar-200 p-5">
                <h2 class="text-sm font-semibold text-gris-900">Se gère ailleurs</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-gris-600">
                    Ce compte accompagne une fiche : classes, enfants ou inscription s’y trouvent.
                    C’est là qu’il se modifie vraiment.
                </p>
                <a href="{{ $moduleDedie['url'] }}" class="bouton-secondaire mt-3 w-full justify-center">
                    Ouvrir « {{ $moduleDedie['libelle'] }} »
                </a>
            </div>
        @endif

        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Accès</h2>
            </div>

            <div class="space-y-2 p-5">
                @if ($soiMeme)
                    <p class="rounded-xl bg-gris-50 px-4 py-3 text-sm text-gris-600">
                        On ne coupe pas son propre accès : il faut un autre compte de direction
                        pour le faire.
                    </p>
                @elseif (! $modifiable)
                    <p class="rounded-xl bg-gris-50 px-4 py-3 text-sm text-gris-600">
                        Un compte de super administrateur ne se modifie que par un
                        super administrateur.
                    </p>
                @else
                    <x-confirmation :action="route('admin.users.toggle-status', $user)"
                                    methode="POST"
                                    :titre="$user->is_active ? 'Désactiver ce compte ?' : 'Réactiver ce compte ?'"
                                    :message="$user->is_active
                                        ? $user->name.' n’aura plus accès à l’application dès sa prochaine visite.'
                                        : $user->name.' pourra de nouveau se connecter.'"
                                    :confirmer="$user->is_active ? 'Désactiver' : 'Réactiver'"
                                    :ton="$user->is_active ? 'danger' : 'primaire'"
                                    bouton="bouton-secondaire w-full justify-center">
                        {{ $user->is_active ? 'Désactiver le compte' : 'Réactiver le compte' }}
                    </x-confirmation>

                    <x-confirmation :action="route('admin.users.destroy', $user)"
                                    methode="DELETE"
                                    titre="Supprimer ce compte ?"
                                    :message="'Le compte de '.$user->name.' sera supprimé. Cette action est définitive.'"
                                    confirmer="Supprimer"
                                    bouton="bouton-secondaire w-full justify-center text-corail-600">
                        Supprimer le compte
                    </x-confirmation>
                @endif
            </div>
        </div>

        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Historique</h2>
            </div>

            <ul class="divide-y divide-gris-100 text-sm">
                <li class="flex items-center justify-between gap-3 px-5 py-3">
                    <span class="text-gris-600">Compte ouvert</span>
                    <span class="text-gris-800">{{ optional($user->created_at)->format('d/m/Y') }}</span>
                </li>
                @if ($user->updated_at && $user->created_at && $user->updated_at->ne($user->created_at))
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <span class="text-gris-600">Dernière modification</span>
                        <span class="text-gris-800">{{ $user->updated_at->format('d/m/Y') }}</span>
                    </li>
                @endif
                <li class="flex items-center justify-between gap-3 px-5 py-3">
                    <span class="text-gris-600">Dernière connexion</span>
                    <span class="text-gris-800">
                        {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y') : 'Jamais' }}
                    </span>
                </li>
            </ul>
        </div>
    </div>
</div>

@endsection
