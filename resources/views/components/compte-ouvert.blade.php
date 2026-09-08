@php
    /*
     * Les identifiants d'un compte qui vient d'être ouvert.
     *
     * Le mot de passe n'est pas conservé en clair : cet encart est le seul
     * endroit où on peut encore le lire. Il est donc franc, imprimable, et il
     * le dit — l'ancienne façon de faire l'annonçait dans un message vert qui
     * disparaissait au premier changement de page.
     */
    $compte = session('compte_ouvert');
@endphp

@if ($compte)
    <div class="carte mb-6 overflow-hidden border-ogar-300" x-data="{ montrer: true }" x-show="montrer">
        <div class="flex items-center justify-between gap-3 border-b border-ogar-200 bg-ogar-50 px-5 py-3">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-ogar-600" fill="none" stroke="currentColor" stroke-width="1.7"
                     viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                </svg>
                <h2 class="text-sm font-semibold text-ogar-800">{{ $compte['titre'] ?? 'Compte ouvert' }}</h2>
            </div>

            <button type="button" @click="montrer = false"
                    class="sans-impression text-xs font-semibold text-gris-500 hover:text-gris-800">
                Masquer
            </button>
        </div>

        <div class="p-5">
            <p class="mb-4 text-sm text-gris-600">
                À remettre à l’intéressé. <strong class="text-gris-900">Ce mot de passe ne sera plus affiché</strong> :
                il n’est pas conservé en clair. En cas de perte, il faudra en engendrer un nouveau.
            </p>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-gris-200 bg-gris-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Identifiant</p>
                    <p class="mt-1 font-mono text-lg font-bold tracking-wide text-gris-900">
                        {{ $compte['identifiant'] ?? '—' }}
                    </p>

                    @if (! empty($compte['courriel']) || ! empty($compte['telephone']))
                        <p class="mt-1.5 text-[11px] leading-relaxed text-gris-500">
                            Fonctionne aussi avec
                            @if (! empty($compte['courriel']))
                                <span class="font-medium text-gris-700">{{ $compte['courriel'] }}</span>
                            @endif
                            @if (! empty($compte['courriel']) && ! empty($compte['telephone'])) ou @endif
                            @if (! empty($compte['telephone']))
                                <span class="font-medium tabular-nums text-gris-700">{{ $compte['telephone'] }}</span>
                            @endif
                        </p>
                    @endif
                </div>

                <div class="rounded-xl border border-ogar-300 bg-ogar-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-ogar-700">Mot de passe initial</p>
                    <p class="mt-1 font-mono text-lg font-bold tracking-[0.2em] text-ogar-900">
                        {{ $compte['mot_de_passe'] }}
                    </p>
                    <p class="mt-1.5 text-[11px] text-ogar-700">À changer à la première connexion.</p>
                </div>
            </div>
        </div>
    </div>
@endif
