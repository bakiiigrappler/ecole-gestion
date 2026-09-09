@php
    /*
     * Menu latéral. Les rubriques viennent de `MenuPrincipal`, partagé avec la
     * barre d'en-tête : les deux dispositions doivent lire le même menu.
     */
    $rubriques = \App\Support\MenuPrincipal::rubriques();
@endphp

<nav class="space-y-6 px-3 py-5">

    {{-- Le tableau de bord reste accessible à tous les profils qui n'ont pas
         le leur dans une rubrique. --}}
    @if (\App\Support\MenuPrincipal::afficherLeTableauDeBord())
        @php($surLeTableauDeBord = request()->routeIs('dashboard'))

        <a href="{{ route('dashboard') }}"
           @if ($surLeTableauDeBord) aria-current="page" @endif
           class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors duration-200
                  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-400
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
        <div>
            <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.12em] text-ogar-300">
                {{ $rubrique['titre'] }}
            </div>

            <ul class="space-y-0.5">
                @foreach ($rubrique['liens'] as $lien)
                    @php($actif = request()->routeIs($lien['motif']))

                    <li>
                        <a href="{{ route($lien['route']) }}"
                           @if ($actif) aria-current="page" @endif
                           class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors duration-200
                                  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-400
                                  {{ $actif
                                        ? 'bg-ogar-600 font-semibold text-white shadow-sm'
                                        : 'text-gris-300 hover:bg-white/10 hover:text-white' }}">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $actif ? 'bg-white' : 'bg-ogar-400/60' }}"></span>
                            <span class="truncate">{{ $lien['libelle'] }}</span>

                            {{-- Ce qui attend une action se compte dans le menu :
                                 c'est le seul endroit visible de partout. --}}
                            @if (! empty($lien['pastille']))
                                <span class="ml-auto inline-flex min-w-5 shrink-0 items-center justify-center rounded-full
                                             bg-corail-500 px-1.5 text-[10px] font-bold text-white">
                                    {{ $lien['pastille'] > 99 ? '99+' : $lien['pastille'] }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
