@php
    /*
     * Navigation en barre d'en-tête, à deux niveaux.
     *
     * La barre sombre porte les rubriques ; la barre claire, en dessous, les
     * liens de la rubrique en cours et les actions de la page. C'est la même
     * arborescence que la barre latérale — elle vient du même `MenuPrincipal` —
     * simplement dépliée à l'horizontale.
     *
     * La rubrique active est celle qui contient le lien actif ; à défaut, la
     * barre du bas ne s'affiche pas.
     */
    $rubriques = \App\Support\MenuPrincipal::rubriques();

    $rubriqueActive = null;

    foreach ($rubriques as $rubrique) {
        foreach ($rubrique['liens'] as $lien) {
            if (request()->routeIs($lien['motif'])) {
                $rubriqueActive = $rubrique;
                break 2;
            }
        }
    }

    $surLeTableauDeBord = request()->routeIs('dashboard');
    $afficherLeTableauDeBord = \App\Support\MenuPrincipal::afficherLeTableauDeBord();
@endphp

{{-- ------------------------------------------------------------------
     Barre principale : les rubriques
     ------------------------------------------------------------------ --}}
<div class="sans-impression sticky top-0 z-30 border-b border-white/10 bg-ardoise-900">
    <div class="mx-auto flex h-14 max-w-[1400px] items-center gap-4 px-4 lg:px-8">

        <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2"
           title="{{ $marque['app_nom'] ?? 'Egesco' }}">
            <x-logo taille="sm" :sombre="true" :application="true"/>
            <span class="hidden text-sm font-bold tracking-tight text-white sm:block">
                {{ $marque['app_nom'] ?? 'Egesco' }}
            </span>
        </a>

        <nav class="defilement-discret flex min-w-0 flex-1 items-center gap-1 overflow-x-auto"
             aria-label="Rubriques">

            @if ($afficherLeTableauDeBord)
                <a href="{{ route('dashboard') }}"
                   @if ($surLeTableauDeBord) aria-current="page" @endif
                   class="flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg px-3 py-1.5 text-sm transition-colors
                          {{ $surLeTableauDeBord
                                ? 'bg-ogar-600 font-semibold text-white shadow-sm'
                                : 'text-gris-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                    </svg>
                    Tableau de bord
                </a>
            @endif

            @foreach ($rubriques as $rubrique)
                @php($active = $rubriqueActive && $rubriqueActive['titre'] === $rubrique['titre'])

                <a href="{{ route($rubrique['liens'][0]['route']) }}"
                   @if ($active) aria-current="page" @endif
                   class="flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg px-3 py-1.5 text-sm transition-colors
                          {{ $active
                                ? 'bg-ogar-600 font-semibold text-white shadow-sm'
                                : 'text-gris-300 hover:bg-white/10 hover:text-white' }}">
                    {{ $rubrique['titre'] }}
                    <span class="rounded-full px-1.5 text-[10px] tabular-nums
                                 {{ $active ? 'bg-white/20 text-white' : 'bg-white/10 text-gris-400' }}">
                        {{ count($rubrique['liens']) }}
                    </span>
                </a>
            @endforeach
        </nav>

        {{-- L'établissement en cours, puis le compte --}}
        <div class="flex shrink-0 items-center gap-3">
            @if ($schoolSettings?->school_name ?? null)
                <span class="hidden max-w-[180px] truncate text-xs text-ogar-200 lg:block"
                      title="{{ $schoolSettings->school_name }}">
                    {{ $schoolSettings->school_name }}
                </span>
            @endif

            @include('partials.compte', ['sombre' => true])
        </div>
    </div>
</div>

{{-- ------------------------------------------------------------------
     Sous-barre : les liens de la rubrique en cours, et les actions
     ------------------------------------------------------------------ --}}
@if ($rubriqueActive)
    <div class="sans-impression sticky top-14 z-20 border-b border-gris-200 bg-white">
        <div class="mx-auto flex max-w-[1400px] items-center gap-4 px-4 lg:px-8">
            <nav class="defilement-discret -mb-px flex min-w-0 flex-1 items-center gap-5 overflow-x-auto"
                 aria-label="{{ $rubriqueActive['titre'] }}">
                @foreach ($rubriqueActive['liens'] as $lien)
                    @php($actif = request()->routeIs($lien['motif']))

                    <a href="{{ route($lien['route']) }}"
                       @if ($actif) aria-current="page" @endif
                       class="flex shrink-0 items-center gap-1.5 whitespace-nowrap border-b-2 py-3 text-sm transition-colors
                              {{ $actif
                                    ? 'border-ogar-600 font-semibold text-ogar-700'
                                    : 'border-transparent text-gris-600 hover:border-gris-300 hover:text-gris-900' }}">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $actif ? 'bg-ogar-600' : 'bg-gris-300' }}"></span>
                        {{ $lien['libelle'] }}

                        {{-- Le meme compteur que dans la barre laterale : les deux
                             dispositions lisent le meme menu. --}}
                        @if (! empty($lien['pastille']))
                            <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-corail-500
                                         px-1.5 text-[10px] font-bold text-white">
                                {{ $lien['pastille'] > 99 ? '99+' : $lien['pastille'] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>

            @hasSection('actions-entete')
                <div class="flex shrink-0 items-center gap-2 py-2">@yield('actions-entete')</div>
            @endif
        </div>
    </div>
@endif
