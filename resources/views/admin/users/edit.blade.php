@extends('layouts.app')

@section('titre', 'Modifier — '.$user->name)
@section('sous-titre', $user->email.' · compte créé le '.optional($user->created_at)->format('d/m/Y'))

@section('actions-entete')
    <a href="{{ route('admin.users.show', $user) }}" class="bouton-secondaire">Voir le compte</a>
    <a href="{{ route('admin.users.index') }}" class="bouton-primaire">Tous les comptes</a>
@endsection

@section('contenu')

@php
    /*
     * Le catalogue fait foi : la liste etait ecrite ici a la main, et elle
     * avait deja diverge de ce que la validation acceptait. Le role superadmin
     * ne s'y trouve que pour un superadmin — le proposer a un chef
     * d'etablissement ne menerait qu'a un refus a l'enregistrement.
     *
     * Le role actuel du compte y est ajoute s'il n'y figure pas : un
     * enseignant ou un parent ouvert depuis son module se modifie parfois
     * ici, et la liste doit pouvoir le montrer sans le changer.
     */
    $roles = \App\Support\Roles::attribuablesPar(auth()->user()->role);

    if (! in_array($user->role, $roles, true)) {
        $roles[] = $user->role;
    }

    $soiMeme = $user->id === auth()->id();
@endphp

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid gap-6 lg:grid-cols-3">
    @csrf
    @method('PUT')

    <div class="space-y-4 lg:col-span-2">

        {{-- ------------------------------------------------------------
             Identité
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Identité</h2>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div>
                    <label for="name" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Nom complet <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="name" id="name" required
                           value="{{ old('name', $user->name) }}" class="champ w-full text-sm">
                    @error('name')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Adresse e-mail
                    </label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email', $user->email) }}" class="champ w-full text-sm">
                    <p class="mt-1 text-[11px] text-gris-400">
                        Facultative : le matricule suffit pour entrer.
                    </p>
                    @error('email')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="telephone" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Téléphone
                    </label>
                    <input type="text" name="telephone" id="telephone"
                           value="{{ old('telephone', $user->telephone) }}" class="champ w-full text-sm"
                           placeholder="077 12 34 56">
                    <p class="mt-1 text-[11px] text-gris-400">Sert aussi d’identifiant de connexion.</p>
                    @error('telephone')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Matricule</span>
                    <p class="rounded-lg border border-gris-200 bg-gris-50 px-3 py-2 font-mono text-sm text-gris-600">
                        {{ $user->matricule ?? '—' }}
                    </p>
                    <p class="mt-1 text-[11px] text-gris-400">Attribué à la création, non modifiable.</p>
                </div>

                <div>
                    <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Dernière modification</span>
                    <p class="rounded-lg border border-gris-200 bg-gris-50 px-3 py-2 text-sm text-gris-600">
                        {{ optional($user->updated_at)->format('d/m/Y à H:i') ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Mot de passe
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Mot de passe</h2>
                    <p class="mt-0.5 text-xs text-gris-400">Laissez ces champs vides pour conserver le mot de passe actuel.</p>
                </div>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div>
                    <label for="password" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Nouveau mot de passe
                    </label>
                    <input type="password" name="password" id="password" autocomplete="new-password"
                           class="champ w-full text-sm">
                    <p class="mt-1 text-[11px] text-gris-400">8 caractères au minimum.</p>
                    @error('password')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Confirmation
                    </label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           autocomplete="new-password" class="champ w-full text-sm">
                </div>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Rôle, accès et enregistrement
         ---------------------------------------------------------------- --}}
    <div class="space-y-4">
        <div class="carte p-5">
            <h2 class="mb-3 text-sm font-semibold text-gris-900">Rôle et accès</h2>

            <div>
                <label for="role" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                    Rôle <span class="text-corail-600">*</span>
                </label>
                <select name="role" id="role" required class="champ w-full text-sm">
                    @foreach ($roles as $cle)
                        <option value="{{ $cle }}" @selected(old('role', $user->role) === $cle)>
                            {{ \App\Support\Roles::libelle($cle) }}
                        </option>
                    @endforeach
                </select>
                @error('role')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror

                @unless (auth()->user()->isSuperAdmin())
                    <p class="mt-1 text-[11px] text-gris-400">
                        Le rôle de super administrateur ne s’attribue qu’entre super administrateurs.
                    </p>
                @endunless
            </div>

            <label class="mt-4 flex cursor-pointer items-start gap-2">
                <input type="checkbox" name="is_active" value="1" class="mt-0.5"
                       @checked(old('is_active', $user->is_active))
                       @disabled($soiMeme)>
                <span class="text-sm">
                    <span class="block font-medium text-gris-800">Compte actif</span>
                    <span class="block text-[11px] text-gris-400">
                        Un compte désactivé ne peut plus se connecter.
                    </span>
                </span>
            </label>

            @if ($soiMeme)
                {{-- Se couper l'accès soi-même n'a pas de rattrapage possible. --}}
                <input type="hidden" name="is_active" value="1">
                <p class="mt-2 rounded-lg bg-soleil-50 p-2 text-[11px] text-soleil-800">
                    Vous ne pouvez pas désactiver votre propre compte.
                </p>
            @endif

            <div class="mt-5 space-y-2 border-t border-gris-100 pt-4">
                <button type="submit" class="bouton-primaire w-full justify-center">Enregistrer</button>
                <a href="{{ route('admin.users.index') }}" class="bouton-secondaire w-full justify-center">Annuler</a>
            </div>
        </div>

        <div class="carte p-5">
            <h2 class="mb-3 text-sm font-semibold text-gris-900">État actuel</h2>

            <div class="flex items-center gap-3">
                <x-avatar :nom="$user->name" class="h-11 w-11 shrink-0 text-xs"/>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-gris-800">{{ $user->name }}</p>
                    <p class="truncate text-[11px] text-gris-400">{{ $user->email }}</p>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <x-puce couleur="violet">{{ $roles[$user->role] ?? $user->role }}</x-puce>
                <x-puce :couleur="$user->is_active ? 'emerald' : 'slate'">
                    {{ $user->is_active ? 'Actif' : 'Désactivé' }}
                </x-puce>
            </div>
        </div>
    </div>
</form>

@endsection
