<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Blade ne compile une directive que si elle n'est pas collee a un mot :
         « @else@yield » laissait « @yield('titre'...) » en clair dans l'onglet
         du navigateur, sur toutes les pages. --}}
    <title>
        @hasSection('title')
            @yield('title')
        @else
            @yield('titre', 'Tableau de bord') &middot; {{ $schoolSettings->school_name ?? ($marque['app_nom'] ?? 'Egesco') }}
        @endif
    </title>

    {{--
        Bootstrap reste chargé le temps de la refonte : les vues pas encore
        converties s'appuient dessus.

        Il est importé dans une couche en cascade nommée, déclarée avant celles
        de Tailwind : sans cela, du CSS hors couche l'emporte sur TOUTE règle
        placée dans une @layer, et Bootstrap écraserait chaque utilitaire
        Tailwind. À retirer une fois la dernière vue passée en Tailwind.
    --}}
    <style>@import url("https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css") layer(bootstrap-transitoire);</style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('head')
    @stack('styles')
</head>
<body class="h-full bg-gris-100 font-sans text-gris-800 antialiased">
<div x-data="{ menuOuvert: false }" class="min-h-full lg:flex">

    {{-- Menu lateral --}}
    {{-- `lg:sticky` plutot que `lg:static` : le menu restait solidaire de la
         page et disparaissait des qu'on descendait dans un formulaire long.
         `h-screen` le borne a la fenetre, `overflow-y-auto` fait defiler la
         navigation elle-meme si elle depasse. --}}
    {{-- Colonne : l'identité et l'année scolaire restent en place, seule la
         navigation défile entre les deux. --}}
    @php
        /*
         * Disposition de la navigation, choisie dans la personnalisation :
         * barre laterale ou barre d'en-tete. En mode « header » la colonne
         * laterale n'est pas rendue du tout — le menu vient de MenuPrincipal,
         * les deux dispositions n'ont rien a se passer.
         */
        $disposition = \App\Support\MenuPrincipal::disposition();
    @endphp

    @if ($disposition === 'sidebar')
    <aside class="sans-impression fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-ardoise-900 transition-transform lg:sticky lg:top-0 lg:h-screen lg:shrink-0 lg:translate-x-0"
           :class="menuOuvert && 'translate-x-0'">

        {{-- Identité de l'application : la même d'un établissement à l'autre. --}}
        <a href="{{ route('dashboard') }}"
           class="flex shrink-0 items-center gap-3 border-b border-white/10 px-5 py-4 transition-colors hover:bg-white/5">
            <x-logo taille="sm" :sombre="true" :application="true"/>
            <span class="min-w-0 leading-tight">
                <span class="block text-base font-bold tracking-tight text-white">{{ $marque['app_nom'] ?? 'Egesco' }}</span>
                @if ($marque['app_slogan'] ?? null)
                    <span class="block text-[11px] text-ogar-200">{{ $marque['app_slogan'] }}</span>
                @endif
            </span>
        </a>

        {{-- L'établissement dans lequel on travaille : distinct de l'application,
             et changeant pour un super administrateur. --}}
        @php($ecole = \App\Support\EcoleCourante::modele())
        <div class="shrink-0 border-b border-white/10 px-5 py-3">
            <span class="block text-[10px] font-semibold uppercase tracking-wider text-white/40">Établissement</span>

            @if ($ecole)
                <span class="mt-0.5 block text-sm font-medium leading-snug text-white">{{ $ecole->name }}</span>
                <span class="block text-[11px] text-ogar-200">
                    {{ $ecole->city ?? '—' }} &middot; <span class="font-mono">{{ $ecole->code }}</span>
                </span>
            @elseif (auth()->user()?->isSuperAdmin())
                <span class="mt-0.5 block text-sm font-medium leading-snug text-white">Tous les établissements</span>
                <a href="{{ route('admin.schools.index') }}" class="block text-[11px] text-ogar-200 hover:underline">
                    Vue d’ensemble — en choisir un
                </a>
            @else
                <span class="mt-0.5 block text-sm font-medium leading-snug text-white">
                    {{ $schoolSettings->school_name ?? '—' }}
                </span>
            @endif
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto defilement-discret defilement-discret--sombre">
            @include('partials.navigation')
        </div>

        {{-- Pied fixe : on lit l'année réellement marquée comme courante, le
             champ texte school_settings.academic_year est saisi à la main et
             dérive vite. --}}
        @php($anneeEnCours = \App\Models\AcademicYear::where('is_current', true)->value('name') ?? ($schoolSettings->academic_year ?? null))

        <div class="shrink-0 border-t border-white/10 px-4 py-3">
            <p class="rounded-lg bg-white/5 px-3 py-2.5 text-[11px] leading-relaxed text-gris-400">
                Année scolaire en cours :
                <span class="block font-semibold text-white">{{ $anneeEnCours ?? '—' }}</span>
            </p>
        </div>
    </aside>

    <div x-show="menuOuvert" x-cloak @click="menuOuvert = false"
         class="fixed inset-0 z-30 bg-gris-900/50 lg:hidden"></div>
    @endif

    {{-- Colonne principale --}}
    <div class="flex min-w-0 flex-1 flex-col">

        @if ($disposition === 'header')
            @include('partials.navigation-entete')
        @else
        <header class="sans-impression sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-gris-200 bg-white px-4 lg:px-6">
            <button @click="menuOuvert = !menuOuvert" class="cursor-pointer rounded-lg p-2 text-gris-500 hover:bg-gris-100 lg:hidden" aria-label="Ouvrir le menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="min-w-0 flex-1">
                <h1 class="truncate text-lg font-semibold text-gris-900">@yield('titre', 'Tableau de bord')</h1>
                @hasSection('sous-titre')
                    <p class="truncate text-xs text-gris-500">@yield('sous-titre')</p>
                @endif
            </div>

            @yield('actions-entete')

            @include('partials.compte')
        </header>
        @endif

        <main class="flex-1 p-4 lg:p-6 {{ $disposition === 'header' ? 'mx-auto w-full max-w-[1400px] lg:px-8' : '' }}">
            @if ($disposition === 'header')
                {{-- En barre d'en-tete, le titre de page n'a plus d'en-tete ou
                     se poser : il ouvre le contenu. --}}
                <div class="mb-5">
                    <h1 class="text-xl font-semibold text-gris-900">@yield('titre', 'Tableau de bord')</h1>
                    @hasSection('sous-titre')
                        <p class="mt-0.5 text-sm text-gris-500">@yield('sous-titre')</p>
                    @endif
                </div>
            @endif

            <x-alertes/>
            {{-- 'contenu' est la section des vues converties, 'content' celle des vues d'origine --}}
            @yield('contenu')
            @yield('content')
        </main>

        <footer class="sans-impression border-t border-gris-200 bg-white px-6 py-3 text-xs text-gris-400">
            {{ $schoolSettings->school_name ?? ($marque['app_nom'] ?? 'Egesco') }} &copy; {{ date('Y') }}
            @if (! empty($schoolSettings?->school_motto))
                &mdash; {{ $schoolSettings->school_motto }}
            @elseif ($marque['pied_de_page'] ?? null)
                &mdash; {{ $marque['pied_de_page'] }}
            @endif
        </footer>
    </div>
</div>

{{-- ------------------------------------------------------------------
     Apercu de disposition : la page s'affiche dans la disposition demandee,
     mais le reglage n'est pas enregistre tant qu'on ne l'a pas choisi.
     ------------------------------------------------------------------ --}}
@if (\App\Support\MenuPrincipal::estUnApercu())
    <div class="sans-impression fixed inset-x-0 bottom-4 z-[95] flex justify-center px-4">
        <div class="flex items-center gap-3 rounded-full border border-ogar-200 bg-white/95 py-2 pl-4 pr-2 text-sm shadow-lg backdrop-blur">
            <span class="inline-flex items-center gap-1.5 font-medium text-ogar-800">
                <svg class="h-4 w-4 text-ogar-600" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Aperçu : {{ $disposition === 'header' ? 'barre d’en-tête' : 'barre latérale' }}
            </span>

            <span class="hidden text-xs text-gris-400 sm:inline">non enregistré</span>

            <a href="{{ route('admin.plateforme.personnalisation', ['nav' => $disposition]) }}"
               class="rounded-full bg-ogar-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-ogar-700">
                Choisir cette disposition
            </a>
        </div>
    </div>
@endif

<style>[x-cloak]{display:none !important}</style>

{{-- Transitionnel, au même titre que la feuille de style Bootstrap ci-dessus --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

@stack('scripts')
</body>
</html>
