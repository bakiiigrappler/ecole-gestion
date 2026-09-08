@extends('layouts.app')

@section('titre', 'Nouveau compte')
@section('sous-titre', 'Ouvrir un accès à l’application')

@section('actions-entete')
    <a href="{{ route('admin.users.index') }}" class="bouton-secondaire">Tous les comptes</a>
@endsection

@section('contenu')

@php
    /*
     * Cet ecran ouvre les comptes du personnel : direction et secretariat.
     *
     * L'enseignant, le parent et l'eleve n'y figurent pas — leur compte nait
     * de leur propre fiche, dans leur module, avec ce qu'elle porte de classes,
     * d'enfants ou d'inscription. Le creer ici donnerait un compte orphelin,
     * sans rien a montrer une fois connecte.
     */
    $roles = \App\Support\Roles::attribuablesPar(auth()->user()->role);
@endphp

<form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-6 lg:grid-cols-3">
    @csrf

    <div class="space-y-4 lg:col-span-2">

        {{-- ------------------------------------------------------------
             Identité
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Identité</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Le matricule est attribué automatiquement.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Nom complet <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="name" id="name" required autofocus
                           value="{{ old('name') }}" class="champ w-full text-sm"
                           placeholder="Prénom et nom">
                    @error('name')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Adresse e-mail
                    </label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email') }}" class="champ w-full text-sm"
                           placeholder="nom@etablissement.ga">
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
                           value="{{ old('telephone') }}" class="champ w-full text-sm"
                           placeholder="077 12 34 56">
                    <p class="mt-1 text-[11px] text-gris-400">
                        Sert aussi d’identifiant de connexion.
                    </p>
                    @error('telephone')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Rôle
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Rôle</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Il commande ce que la personne voit, et ce qu’elle peut faire.
                        Les enseignants, les parents et les élèves reçoivent leur compte
                        depuis leur propre fiche.
                    </p>
                </div>
            </div>

            <div class="grid gap-2 p-5 sm:grid-cols-2">
                @foreach ($roles as $cle)
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="{{ $cle }}" class="peer sr-only"
                               @checked(old('role') === $cle)>
                        <span class="block rounded-xl border border-gris-200 px-4 py-3 transition
                                     peer-checked:border-ogar-500 peer-checked:bg-ogar-50">
                            <span class="block text-sm font-semibold text-gris-800">
                                {{ \App\Support\Roles::libelle($cle) }}
                            </span>
                            <span class="mt-0.5 block text-[11px] text-gris-500">
                                {{ \App\Support\Roles::detail($cle) }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            @error('role')
                <p class="border-t border-gris-100 px-5 py-2 text-[11px] text-corail-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- ------------------------------------------------------------
         Colonne latérale
         ------------------------------------------------------------ --}}
    <div class="space-y-4">

        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Accès</h2>
            </div>

            <div class="space-y-4 p-5">
                <label class="flex cursor-pointer items-start gap-3"
                       x-data="{ actif: {{ old('is_active', true) ? 'true' : 'false' }} }">
                    <span class="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full transition"
                          :class="actif ? 'bg-ogar-600' : 'bg-gris-300'">
                        <input type="checkbox" name="is_active" value="1" x-model="actif" class="sr-only">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition"
                              :class="actif ? 'translate-x-[18px]' : 'translate-x-1'"></span>
                    </span>
                    <span>
                        <span class="block text-sm font-medium text-gris-800">Compte actif</span>
                        <span class="block text-[11px] text-gris-500">
                            Désactivé, il existe mais ne permet plus d’entrer.
                        </span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Ce qui se passe à l'enregistrement, dit d'avance --}}
        <div class="carte overflow-hidden border-ogar-200">
            <div class="border-b border-ogar-200 bg-ogar-50 px-5 py-3">
                <h2 class="text-sm font-semibold text-ogar-800">Mot de passe</h2>
            </div>

            <div class="p-5 text-sm leading-relaxed text-gris-600">
                <p>
                    Un mot de passe est <strong class="text-gris-900">engendré à l’enregistrement</strong> et
                    affiché une seule fois sur l’écran suivant.
                </p>
                <p class="mt-2 text-[11px] text-gris-500">
                    Notez-le pour le remettre à l’intéressé : il n’est pas conservé en clair, et
                    ne pourra plus être relu. En cas de perte, il faudra en engendrer un nouveau.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <button type="submit" class="bouton-primaire w-full justify-center">Créer le compte</button>
            <a href="{{ route('admin.users.index') }}" class="bouton-secondaire w-full justify-center">Annuler</a>
        </div>
    </div>
</form>

@endsection
