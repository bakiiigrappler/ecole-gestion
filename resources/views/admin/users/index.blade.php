@extends('layouts.app')

@section('titre', 'Comptes utilisateurs')
@section('sous-titre', $users->total().' compte(s) — les super administrateurs ne figurent pas dans cette liste')

@section('actions-entete')
    <a href="{{ route('admin.settings.index') }}" class="bouton-secondaire">Paramètres généraux</a>
    <a href="{{ route('admin.users.create') }}" class="bouton-primaire">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        <span class="hidden sm:inline">Nouveau compte</span>
    </a>
@endsection

@section('contenu')

@php
    /*
     * Cet ecran ne tient que le personnel : direction et secretariat. Les
     * enseignants, les parents et les eleves se gerent depuis leur module —
     * les melanger ici noyait la dizaine de comptes qui se gere vraiment sous
     * les neuf cents autres.
     */
    $roles = $roles ?? \App\Support\Roles::personnel();
    $roles = array_values(array_diff($roles, ['superadmin']));

    // Classes écrites en entier : Tailwind ne compile pas une teinte interpolée.
    $puceRole = [
        'admin' => 'violet', 'directeur' => 'indigo', 'proviseur' => 'indigo',
        'censeur' => 'sky', 'secretary' => 'sky',
    ];

    $filtres = request()->only(['search', 'role', 'status']);
    $filtreActif = collect($filtres)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

    $parRole = $users->getCollection()->countBy('role');
    $direction = collect(\App\Support\Roles::direction())->sum(fn ($r) => $parRole[$r] ?? 0);
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Comptes" :valeur="$users->total()"
                       :detail="$filtreActif ? 'après filtrage' : 'hors super administrateurs'"
                       couleur="ogar"/>
        <x-statistique libelle="Direction" :valeur="$direction"
                       detail="Administration et chefs d’établissement" couleur="violet"/>
        <x-statistique libelle="Secrétariat" :valeur="$parRole['secretary'] ?? 0"
                       detail="Inscriptions et encaissements" couleur="sky"/>
        <x-statistique libelle="Désactivés" :valeur="$users->getCollection()->where('is_active', false)->count()"
                       detail="N’entrent plus dans l’application" couleur="slate"/>
    </div>

    {{-- ----------------------------------------------------------------
         Filtres
         ---------------------------------------------------------------- --}}
    <form method="GET" action="{{ route('admin.users.index') }}"
          class="carte mt-6 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-48 flex-1">
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Recherche</label>
            <input type="search" name="search" value="{{ request('search') }}"
                   placeholder="Nom ou courriel…" class="champ w-full text-sm">
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Rôle</label>
            <select name="role" class="champ w-44 text-sm">
                <option value="">Tous</option>
                @foreach ($roles as $cle)
                    <option value="{{ $cle }}" @selected(request('role') === $cle)>
                        {{ \App\Support\Roles::libelle($cle) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Statut</label>
            <select name="status" class="champ w-36 text-sm">
                <option value="">Tous</option>
                <option value="active" @selected(request('status') === 'active')>Actif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Désactivé</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bouton-primaire">Filtrer</button>
            @if ($filtreActif)
                <a href="{{ route('admin.users.index') }}" class="bouton-secondaire">Réinitialiser</a>
            @endif
        </div>
    </form>

    {{-- ----------------------------------------------------------------
         Les comptes
         ---------------------------------------------------------------- --}}
    <div class="carte mt-4 overflow-hidden">
        <div class="carte-entete">
            <h2 class="text-sm font-semibold text-gris-900">Liste des comptes</h2>
            <span class="text-xs text-gris-400">
                {{ $users->total() }} compte(s){{ $filtreActif ? ' après filtrage' : '' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Matricule</th>
                        <th>Rôle et droits d’accès</th>
                        <th>Statut</th>
                        <th>Créé le</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $utilisateur)
                        <tr class="{{ $utilisateur->is_active ? '' : 'opacity-60' }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :nom="$utilisateur->name" class="h-9 w-9 shrink-0 text-[11px]"/>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.users.show', $utilisateur) }}"
                                           class="block truncate font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                            {{ $utilisateur->name }}
                                        </a>
                                        <div class="truncate text-[11px] text-gris-400">{{ $utilisateur->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="font-mono text-[11px] text-gris-500">{{ $utilisateur->matricule ?? '—' }}</td>
                            <td>
                                <x-puce :couleur="$puceRole[$utilisateur->role] ?? 'slate'">
                                    {{ \App\Support\Roles::libelle($utilisateur->role) }}
                                </x-puce>
                                {{-- Nommer un rôle ne dit pas ce qu'il permet :
                                     « censeur » ne se lit pas tout seul. --}}
                                <div class="mt-1 text-[11px] leading-snug text-gris-500">
                                    {{ \App\Support\Roles::detail($utilisateur->role) }}
                                </div>
                            </td>
                            <td>
                                <x-puce :couleur="$utilisateur->is_active ? 'emerald' : 'slate'">
                                    {{ $utilisateur->is_active ? 'Actif' : 'Désactivé' }}
                                </x-puce>
                            </td>
                            <td class="whitespace-nowrap text-gris-600">
                                {{ optional($utilisateur->created_at)->format('d/m/Y') }}
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Le mot de passe se rejoue depuis le detail : il n'est
                                         conserve nulle part en clair. --}}
                                    <a href="{{ route('admin.users.show', $utilisateur) }}" class="bouton-mini">Détails</a>
                                    <a href="{{ route('admin.users.edit', $utilisateur) }}" class="bouton-mini">Modifier</a>

                                    {{-- Activer ou couper un accès mérite une confirmation
                                         explicite : la personne perd l'application sur-le-champ. --}}
                                    <x-confirmation :action="route('admin.users.toggle-status', $utilisateur)"
                                                    methode="POST"
                                                    :titre="$utilisateur->is_active ? 'Désactiver ce compte ?' : 'Réactiver ce compte ?'"
                                                    :message="$utilisateur->is_active
                                                        ? $utilisateur->name.' n’aura plus accès à l’application dès sa prochaine visite.'
                                                        : $utilisateur->name.' pourra de nouveau se connecter.'"
                                                    :confirmer="$utilisateur->is_active ? 'Désactiver' : 'Réactiver'"
                                                    :ton="$utilisateur->is_active ? 'danger' : 'primaire'"
                                                    bouton="bouton-mini">
                                        {{ $utilisateur->is_active ? 'Désactiver' : 'Réactiver' }}
                                    </x-confirmation>

                                    <x-confirmation :action="route('admin.users.destroy', $utilisateur)"
                                                    methode="DELETE"
                                                    titre="Supprimer ce compte ?"
                                                    :message="'Le compte de '.$utilisateur->name.' ('.$utilisateur->email.') sera supprimé. Cette action est définitive.'"
                                                    confirmer="Supprimer"
                                                    bouton="bouton-mini text-corail-600">
                                        Supprimer
                                    </x-confirmation>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="6" message="Aucun compte ne correspond à ces filtres."/>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-gris-200 px-4 py-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>

@endsection
