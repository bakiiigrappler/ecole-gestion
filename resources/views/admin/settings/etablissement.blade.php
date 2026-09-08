@extends('layouts.app')

@section('titre', 'Paramètres de l’établissement')
@section('sous-titre', 'Ce qui figure sur les bulletins, emplois du temps et documents officiels')

@section('actions-entete')
    <a href="{{ route('admin.school-settings.preview') }}" class="bouton-secondaire">Aperçu</a>
    <a href="{{ route('admin.settings.index') }}" class="bouton-primaire">Paramètres généraux</a>
@endsection

@section('contenu')

@php
    $fuseaux = ['Africa/Libreville', 'Africa/Douala', 'Africa/Abidjan', 'Africa/Dakar', 'Europe/Paris', 'UTC'];
    $devises = ['FCFA', 'EUR', 'USD'];
    $langues = ['fr' => 'Français', 'en' => 'Anglais'];
@endphp

<form method="POST" action="{{ route('admin.school-settings.update', $settings->id) }}"
      enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
    @csrf
    @method('PUT')

    <div class="space-y-4 lg:col-span-2">

        {{-- ------------------------------------------------------------
             Identité — ce qui s'imprime en tête des documents
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Identité de l’établissement</h2>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="school_name" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Nom affiché
                    </label>
                    <input type="text" name="school_name" id="school_name"
                           value="{{ old('school_name', $settings->school_name) }}" class="champ w-full text-sm">
                    <p class="mt-1 text-[11px] text-gris-400">Nom court, utilisé dans l’interface et l’en-tête des documents.</p>
                    @error('school_name')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="primary_school_name" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Nom du complexe scolaire <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="primary_school_name" id="primary_school_name" required
                           value="{{ old('primary_school_name', $settings->primary_school_name) }}" class="champ w-full text-sm">
                    <p class="mt-1 text-[11px] text-gris-400">Préprimaire et primaire — bulletins et attestations.</p>
                    @error('primary_school_name')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="secondary_school_name" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Nom du collège / lycée <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="secondary_school_name" id="secondary_school_name" required
                           value="{{ old('secondary_school_name', $settings->secondary_school_name) }}" class="champ w-full text-sm">
                    <p class="mt-1 text-[11px] text-gris-400">Collège et lycée — bulletins et attestations.</p>
                    @error('secondary_school_name')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="school_motto" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Devise
                    </label>
                    <input type="text" name="school_motto" id="school_motto"
                           value="{{ old('school_motto', $settings->school_motto) }}" class="champ w-full text-sm">
                </div>

                <div>
                    <label for="academic_year" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Année scolaire <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="academic_year" id="academic_year" required
                           value="{{ old('academic_year', $settings->academic_year) }}" class="champ w-full text-sm">
                    @error('academic_year')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label for="school_description" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Description
                    </label>
                    <textarea name="school_description" id="school_description" rows="2"
                              class="champ w-full text-sm">{{ old('school_description', $settings->school_description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Contact
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Contact et localisation</h2>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="school_address" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Adresse
                    </label>
                    <input type="text" name="school_address" id="school_address"
                           value="{{ old('school_address', $settings->school_address) }}" class="champ w-full text-sm">
                </div>

                <div>
                    <label for="school_bp" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Boîte postale <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="school_bp" id="school_bp" required
                           value="{{ old('school_bp', $settings->school_bp) }}" class="champ w-full text-sm">
                    @error('school_bp')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="school_phone" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Téléphone <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="school_phone" id="school_phone" required
                           value="{{ old('school_phone', $settings->school_phone) }}" class="champ w-full text-sm">
                    @error('school_phone')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="school_email" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Courriel
                    </label>
                    <input type="email" name="school_email" id="school_email"
                           value="{{ old('school_email', $settings->school_email) }}" class="champ w-full text-sm">
                    @error('school_email')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="school_website" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Site web
                    </label>
                    <input type="url" name="school_website" id="school_website"
                           value="{{ old('school_website', $settings->school_website) }}" class="champ w-full text-sm">
                    @error('school_website')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="city" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Ville <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="city" id="city" required
                           value="{{ old('city', $settings->city) }}" class="champ w-full text-sm">
                </div>

                <div>
                    <label for="country" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Pays <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="country" id="country" required
                           value="{{ old('country', $settings->country) }}" class="champ w-full text-sm">
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Direction et réglages
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Direction et réglages</h2>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div>
                    <label for="principal_name" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Nom du chef d’établissement
                    </label>
                    <input type="text" name="principal_name" id="principal_name"
                           value="{{ old('principal_name', $settings->principal_name) }}" class="champ w-full text-sm">
                </div>

                <div>
                    <label for="principal_title" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Titre <span class="text-corail-600">*</span>
                    </label>
                    <input type="text" name="principal_title" id="principal_title" required
                           value="{{ old('principal_title', $settings->principal_title) }}" class="champ w-full text-sm">
                    <p class="mt-1 text-[11px] text-gris-400">Apparaît au-dessus de la signature sur les documents.</p>
                    @error('principal_title')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="timezone" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Fuseau horaire <span class="text-corail-600">*</span>
                    </label>
                    <select name="timezone" id="timezone" required class="champ w-full text-sm">
                        @foreach ($fuseaux as $fuseau)
                            <option value="{{ $fuseau }}" @selected(old('timezone', $settings->timezone) === $fuseau)>{{ $fuseau }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="currency" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Devise <span class="text-corail-600">*</span>
                    </label>
                    <select name="currency" id="currency" required class="champ w-full text-sm">
                        @foreach ($devises as $devise)
                            <option value="{{ $devise }}" @selected(old('currency', $settings->currency) === $devise)>{{ $devise }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="language" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Langue <span class="text-corail-600">*</span>
                    </label>
                    <select name="language" id="language" required class="champ w-full text-sm">
                        @foreach ($langues as $code => $libelle)
                            <option value="{{ $code }}" @selected(old('language', $settings->language) === $code)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-4 pb-2">
                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                        <input type="checkbox" name="has_primary" value="1"
                               @checked(old('has_primary', $settings->has_primary))>
                        <span class="text-gris-700">Préprimaire et primaire</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                        <input type="checkbox" name="has_secondary" value="1"
                               @checked(old('has_secondary', $settings->has_secondary))>
                        <span class="text-gris-700">Collège et lycée</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------
             Paiement par téléphone
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden" x-data="{ ouvert: {{ old('mobile_money_actif', $settings->mobile_money_actif) ? 'true' : 'false' }} }">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Paiement par téléphone</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Ce que le parent voit sur son portail quand il vient régler la scolarité.
                    </p>
                </div>
            </div>

            <div class="space-y-4 p-5">
                <label class="flex cursor-pointer items-start gap-3">
                    <span class="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full transition"
                          :class="ouvert ? 'bg-ogar-600' : 'bg-gris-300'">
                        <input type="checkbox" name="mobile_money_actif" value="1" x-model="ouvert" class="sr-only">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition"
                              :class="ouvert ? 'translate-x-[18px]' : 'translate-x-1'"></span>
                    </span>
                    <span>
                        <span class="block text-sm font-medium text-gris-800">Ouvrir le paiement mobile money</span>
                        <span class="block text-[11px] text-gris-500">
                            Le parent voit alors le code marchand et la marche à suivre, puis déclare
                            son versement. Rien n’est encaissé ici : le secrétariat vérifie le SMS de
                            l’opérateur avant de valider.
                        </span>
                    </span>
                </label>

                <div x-show="ouvert" x-cloak class="space-y-4 border-t border-gris-100 pt-4">
                    @foreach (\App\Support\MobileMoney::OPERATEURS as $cle => $operateur)
                        {{-- Chaque opérateur a son propre interrupteur : une école peut
                             n'avoir de compte que chez l'un des deux, ou fermer l'un
                             d'eux sans effacer ses coordonnées. --}}
                        <div class="rounded-xl border border-gris-200 p-4"
                             x-data="{ ouvert: {{ old($operateur['champ_actif'], $settings->{$operateur['champ_actif']}) ? 'true' : 'false' }} }">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <label class="flex cursor-pointer items-center gap-3">
                                    <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition"
                                          :class="ouvert ? 'bg-ogar-600' : 'bg-gris-300'">
                                        <input type="checkbox" name="{{ $operateur['champ_actif'] }}" value="1"
                                               x-model="ouvert" class="sr-only">
                                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition"
                                              :class="ouvert ? 'translate-x-[18px]' : 'translate-x-1'"></span>
                                    </span>
                                    <span class="text-sm font-semibold text-gris-800">{{ $operateur['libelle'] }}</span>
                                </label>

                                <span class="font-mono text-[11px] text-gris-400">{{ $operateur['ussd'] }}</span>
                            </div>

                            <div x-show="ouvert" x-cloak class="mt-4 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label for="{{ $operateur['champ_numero'] }}"
                                           class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                                        Numéro {{ $operateur['libelle'] }}
                                    </label>
                                    <input type="text" name="{{ $operateur['champ_numero'] }}" id="{{ $operateur['champ_numero'] }}"
                                           value="{{ old($operateur['champ_numero'], $settings->{$operateur['champ_numero']}) }}"
                                           class="champ w-full text-sm tabular-nums" placeholder="Ex. 077 12 34 56">
                                    <p class="mt-1 text-[11px] text-gris-400">
                                        Le compte sur lequel les parents versent, par transfert.
                                    </p>
                                    @error($operateur['champ_numero'])<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="{{ $operateur['champ_code'] }}"
                                           class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                                        Code marchand
                                    </label>
                                    <input type="text" name="{{ $operateur['champ_code'] }}" id="{{ $operateur['champ_code'] }}"
                                           value="{{ old($operateur['champ_code'], $settings->{$operateur['champ_code']}) }}"
                                           class="champ w-full text-sm" placeholder="Ex. 123456">
                                    <p class="mt-1 text-[11px] text-gris-400">
                                        Si l’école a un compte marchand : le parent paie alors par
                                        « paiement marchand » plutôt que par transfert.
                                    </p>
                                    @error($operateur['champ_code'])<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="{{ $operateur['champ_nom'] }}"
                                           class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                                        Nom affiché au téléphone
                                    </label>
                                    <input type="text" name="{{ $operateur['champ_nom'] }}" id="{{ $operateur['champ_nom'] }}"
                                           value="{{ old($operateur['champ_nom'], $settings->{$operateur['champ_nom']}) }}"
                                           class="champ w-full text-sm" placeholder="{{ $settings->school_name }}">
                                    <p class="mt-1 text-[11px] text-gris-400">
                                        Le parent doit pouvoir vérifier qu’il paie bien l’école avant de valider.
                                    </p>
                                    @error($operateur['champ_nom'])<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                                </div>

                                <p class="text-[11px] text-gris-400 md:col-span-2">
                                    Sans numéro ni code marchand, cet opérateur n’est pas proposé aux parents :
                                    l’écran n’aurait rien à leur indiquer.
                                </p>
                            </div>
                        </div>
                    @endforeach

                    <div>
                        <label for="mobile_money_consignes"
                               class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Vos propres consignes
                        </label>
                        <textarea name="mobile_money_consignes" id="mobile_money_consignes" rows="4"
                                  class="champ w-full text-sm"
                                  placeholder="Une étape par ligne. Laissez vide pour la marche à suivre habituelle.">{{ old('mobile_money_consignes', $settings->mobile_money_consignes) }}</textarea>
                        <p class="mt-1 text-[11px] text-gris-400">
                            Une ligne par étape. Renseignées, elles remplacent la marche à suivre
                            proposée par défaut.
                        </p>
                        @error('mobile_money_consignes')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Emblèmes et enregistrement
         ---------------------------------------------------------------- --}}
    <div class="space-y-4">
        <div class="carte p-5">
            <h2 class="mb-1 text-sm font-semibold text-gris-900">Emblèmes</h2>
            <p class="mb-4 text-[11px] text-gris-400">
                Le logo se place à gauche et le sceau à droite de l’en-tête des bulletins,
                emplois du temps et rapports.
            </p>

            <div class="space-y-4">
                <div>
                    <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Logo de l’établissement
                    </span>
                    <div class="flex items-center gap-3">
                        @if ($settings->logo_url)
                            <img src="{{ $settings->logo_url }}" alt="Logo actuel"
                                 class="h-14 w-14 shrink-0 rounded border border-gris-200 object-contain p-1">
                        @else
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded border border-dashed border-gris-300 text-center text-[9px] text-gris-400">
                                Aucun
                            </span>
                        @endif
                        <input type="file" name="school_logo" accept="image/*" class="w-full text-xs">
                    </div>
                    @error('school_logo')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Sceau de la République
                    </span>
                    <div class="flex items-center gap-3">
                        @if ($settings->seal_url)
                            <img src="{{ $settings->seal_url }}" alt="Sceau actuel"
                                 class="h-14 w-20 shrink-0 rounded border border-gris-200 object-contain p-1">
                        @else
                            <span class="flex h-14 w-20 shrink-0 items-center justify-center rounded border border-dashed border-gris-300 text-center text-[9px] text-gris-400">
                                Aucun
                            </span>
                        @endif
                        <input type="file" name="school_seal" accept="image/*" class="w-full text-xs">
                    </div>
                    @error('school_seal')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <p class="mt-3 text-[11px] text-gris-400">JPG, PNG ou GIF — 2 Mo au maximum.</p>

            <div class="mt-5 space-y-2 border-t border-gris-100 pt-4">
                <button type="submit" class="bouton-primaire w-full justify-center">Enregistrer les paramètres</button>
                <a href="{{ route('admin.settings.index') }}" class="bouton-secondaire w-full justify-center">Annuler</a>
            </div>
        </div>
    </div>
</form>

@endsection
