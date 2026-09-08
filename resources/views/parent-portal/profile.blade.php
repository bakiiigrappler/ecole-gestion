@extends('layouts.app')

@section('titre', 'Mon profil')
@section('sous-titre', $parent->first_name.' '.$parent->last_name.' · '.($user->email ?? ''))

@section('actions-entete')
    <a href="{{ route('parent-portal.dashboard') }}" class="bouton-secondaire">Mes enfants</a>
@endsection

@section('contenu')

    <div class="grid items-start gap-4 lg:grid-cols-3">

        {{-- ------------------------------------------------------------
             Mes coordonnées
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden lg:col-span-2">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Mes coordonnées</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        L’établissement s’en sert pour vous joindre au sujet de vos enfants.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('parent-portal.update-profile') }}" class="space-y-4 p-5">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="etiquette">Prénom</label>
                        <input type="text" name="first_name" id="first_name" class="champ"
                               value="{{ old('first_name', $parent->first_name) }}" required>
                        @error('first_name') <p class="mt-1 text-xs text-corail-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="last_name" class="etiquette">Nom</label>
                        <input type="text" name="last_name" id="last_name" class="champ"
                               value="{{ old('last_name', $parent->last_name) }}" required>
                        @error('last_name') <p class="mt-1 text-xs text-corail-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="etiquette">Adresse e-mail</label>
                        <input type="email" name="email" id="email" class="champ"
                               value="{{ old('email', $user->email) }}" required>
                        @error('email') <p class="mt-1 text-xs text-corail-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="etiquette">Téléphone</label>
                        <input type="text" name="phone" id="phone" class="champ"
                               value="{{ old('phone', $parent->phone) }}" required>
                        @error('phone') <p class="mt-1 text-xs text-corail-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="address" class="etiquette">Adresse</label>
                    <textarea name="address" id="address" rows="2" class="champ">{{ old('address', $parent->address) }}</textarea>
                    @error('address') <p class="mt-1 text-xs text-corail-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bouton-primaire">Enregistrer</button>
                </div>
            </form>
        </div>

        {{-- ------------------------------------------------------------
             Mon mot de passe
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Mon mot de passe</h2>
            </div>

            <form method="POST" action="{{ route('parent-portal.change-password') }}" class="space-y-4 p-5">
                @csrf

                <div>
                    <label for="current_password" class="etiquette">Mot de passe actuel</label>
                    <input type="password" name="current_password" id="current_password" class="champ" required>
                    @error('current_password') <p class="mt-1 text-xs text-corail-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="etiquette">Nouveau mot de passe</label>
                    <input type="password" name="password" id="password" class="champ" required>
                    @error('password') <p class="mt-1 text-xs text-corail-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="etiquette">Confirmation</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="champ" required>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bouton-secondaire">Changer</button>
                </div>
            </form>

            <div class="border-t border-gris-100 bg-gris-50 px-5 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-400">Mes enfants</p>
                <ul class="mt-2 space-y-1 text-sm text-gris-700">
                    @forelse ($parent->students as $enfant)
                        <li>
                            <a href="{{ route('parent-portal.child-details', $enfant->id) }}"
                               class="hover:text-ogar-600 hover:underline">
                                {{ $enfant->first_name }} {{ $enfant->last_name }}
                            </a>
                        </li>
                    @empty
                        <li class="text-gris-400">Aucun enfant rattaché.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

@endsection
