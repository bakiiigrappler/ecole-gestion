@props(['message' => 'Chargement…'])

{{--
    Voile de chargement d'une liste : la toque de l'emblème s'anime le temps
    de la requête. Il recouvre son conteneur, qui doit être en `relative`.

    Il est masqué par défaut ; `pagination-dynamique.js` le révèle en retirant
    l'attribut `hidden`.
--}}

<div {{ $attributes->merge([
        'class' => 'voile-chargement absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-white/80 backdrop-blur-[2px]',
        'role' => 'status',
        'aria-live' => 'polite',
    ]) }}>

    <span class="relative flex h-24 w-24 items-center justify-center">
        {{-- Anneau qui tourne autour de la mascotte --}}
        <svg class="anneau-tourne absolute inset-0 h-24 w-24" viewBox="0 0 80 80" fill="none" aria-hidden="true">
            <circle cx="40" cy="40" r="37" stroke="#ddeefa" stroke-width="3"/>
            <path d="M40 3a37 37 0 0 1 37 37" stroke="#1c75bc" stroke-width="3" stroke-linecap="round"/>
        </svg>

        {{-- Le hibou bat des ailes le temps de la requête --}}
        <x-mascotte pose="chargement" taille="h-16"/>
    </span>

    <span class="text-xs font-semibold uppercase tracking-wide text-gris-500">{{ $message }}</span>
</div>
