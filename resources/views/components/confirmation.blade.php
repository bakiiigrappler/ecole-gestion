@props([
    'action',
    'methode' => 'POST',
    'titre' => 'Confirmer l\'action',
    'message' => 'Cette action est définitive.',
    'confirmer' => 'Confirmer',
    'ton' => 'danger',
    'bouton' => 'text-xs font-semibold text-corail-600 hover:underline',
])

{{--
    Bouton d'action protégé par un modal de confirmation.

    Le contenu du slot sert de libellé au bouton déclencheur ; le formulaire
    n'est soumis qu'après confirmation explicite. Le panneau est téléporté dans
    <body> pour ne pas être rogné par les conteneurs à défilement (tableaux).

    Usage :
      <x-confirmation :action="route('...')" methode="DELETE"
                      titre="Supprimer" message="..." confirmer="Supprimer">
          Supprimer
      </x-confirmation>
--}}

@php
    $identifiant = 'confirmation-'.uniqid();

    $tons = [
        'danger' => [
            'pastille' => 'bg-corail-100 text-corail-600',
            'action' => 'bouton-danger',
        ],
        'primaire' => [
            'pastille' => 'bg-ogar-100 text-ogar-700',
            'action' => 'bouton-primaire',
        ],
    ];

    $apparence = $tons[$ton] ?? $tons['danger'];
@endphp

<div x-data="{ confirme: false }" class="contents">

    <button type="button" @click="confirme = true"
            class="cursor-pointer {{ $bouton }}"
            aria-haspopup="dialog"
            :aria-expanded="confirme">{{ $slot }}</button>

    <template x-teleport="body">
        <div x-show="confirme" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="{{ $identifiant }}-titre"
             @keydown.escape.window="confirme = false"
             x-effect="confirme && $nextTick(() => $refs.valider?.focus())">

            <div x-show="confirme" x-transition.opacity.duration.200ms
                 class="absolute inset-0 bg-gris-900/50 motion-reduce:transition-none"
                 @click="confirme = false"></div>

            <div x-show="confirme"
                 x-transition:enter="transition duration-200 ease-out motion-reduce:transition-none"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">

                <div class="flex gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $apparence['pastille'] }}">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h2 id="{{ $identifiant }}-titre" class="text-base font-semibold text-gris-900">{{ $titre }}</h2>
                        <p class="mt-1 text-sm text-gris-600">{{ $message }}</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="confirme = false" class="bouton-secondaire cursor-pointer">Annuler</button>

                    <form method="POST" action="{{ $action }}">
                        @csrf
                        @if (! in_array(strtoupper($methode), ['GET', 'POST'], true))
                            @method($methode)
                        @endif
                        {{ $champs ?? '' }}
                        <button x-ref="valider" class="{{ $apparence['action'] }} cursor-pointer">{{ $confirmer }}</button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
