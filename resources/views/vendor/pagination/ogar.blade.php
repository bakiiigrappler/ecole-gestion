{{--
    Pagination aux couleurs de la charte.

    Vue maison plutôt que celle du framework : les vues de pagination livrées
    par Laravel vivent sous vendor/, que Tailwind ne parcourt pas — leurs
    classes ne seraient donc jamais générées à la compilation des assets.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4">

        {{-- Mobile : précédent / suivant --}}
        <div class="flex flex-1 items-center justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="cursor-not-allowed rounded-lg border border-gris-200 bg-gris-50 px-3 py-2 text-sm font-medium text-gris-400">
                    Précédent
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="cursor-pointer rounded-lg border border-gris-300 bg-white px-3 py-2 text-sm font-medium text-gris-700 transition-colors hover:bg-gris-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-600">
                    Précédent
                </a>
            @endif

            <span class="text-xs text-gris-500">
                Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="cursor-pointer rounded-lg border border-gris-300 bg-white px-3 py-2 text-sm font-medium text-gris-700 transition-colors hover:bg-gris-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-600">
                    Suivant
                </a>
            @else
                <span class="cursor-not-allowed rounded-lg border border-gris-200 bg-gris-50 px-3 py-2 text-sm font-medium text-gris-400">
                    Suivant
                </span>
            @endif
        </div>

        {{-- Écrans larges : compteur + numéros de page --}}
        <div class="hidden flex-1 items-center justify-between sm:flex">
            <p class="text-sm text-gris-500">
                <span class="font-semibold text-gris-700">{{ $paginator->firstItem() }}</span>
                à
                <span class="font-semibold text-gris-700">{{ $paginator->lastItem() }}</span>
                sur
                <span class="font-semibold text-gris-700">{{ $paginator->total() }}</span>
                résultat{{ $paginator->total() > 1 ? 's' : '' }}
            </p>

            <div class="flex items-center gap-1">

                {{-- Précédent --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Page précédente"
                          class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gris-200 bg-gris-50 text-gris-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                        </svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Page précédente"
                       class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border border-gris-300 bg-white text-gris-600 transition-colors hover:border-ogar-300 hover:bg-ogar-50 hover:text-ogar-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                        </svg>
                    </a>
                @endif

                {{-- Numéros --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-2 text-sm text-gris-400">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-ogar-600 px-3 text-sm font-semibold text-white">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" aria-label="Page {{ $page }}"
                                   class="flex h-9 min-w-9 cursor-pointer items-center justify-center rounded-lg border border-gris-300 bg-white px-3 text-sm font-medium text-gris-600 transition-colors hover:border-ogar-300 hover:bg-ogar-50 hover:text-ogar-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-600">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Suivant --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Page suivante"
                       class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border border-gris-300 bg-white text-gris-600 transition-colors hover:border-ogar-300 hover:bg-ogar-50 hover:text-ogar-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="Page suivante"
                          class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gris-200 bg-gris-50 text-gris-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
