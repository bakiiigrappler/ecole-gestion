@props([
    'sombre' => false,      // true : pose sur un fond sombre
    'taille' => 'md',       // sm | md | lg | xl | 2xl
    'application' => false, // true : l'emblème d'Egesco, pas celui de l'école
])

@php
    /*
     * Logo de l'établissement, téléversé depuis les paramètres. À défaut, on
     * dessine l'emblème par défaut : une toque universitaire aux couleurs de la
     * charte, qui reste lisible à toutes les tailles.
     *
     * Sur fond sombre, un logo téléversé peut disparaître (fond blanc, traits
     * clairs) : il est alors posé sur une pastille blanche.
     */
    // L'emblème de l'application ne dépend d'aucun établissement : la barre
    // latérale doit rester la même d'une école à l'autre. Il vient de la
    // personnalisation de la plateforme, et à défaut du dessin ci-dessous.
    $parametres = $application ? null : ($schoolSettings ?? \App\Models\SchoolSettings::getSettings());

    $url = $application
        ? \App\Support\Marque::logoUrl()
        : $parametres?->logo_url;

    $nom = $application
        ? \App\Support\Marque::nom()
        : ($parametres?->school_name ?? \App\Support\Marque::nom());

    $hauteurs = [
        'sm'  => 'h-8',
        'md'  => 'h-10',
        'lg'  => 'h-14',
        'xl'  => 'h-16',
        '2xl' => 'h-20',
    ];
    $hauteur = $hauteurs[$taille] ?? $hauteurs['md'];

    $habillage = $sombre && $url ? 'rounded-lg bg-white px-2.5 py-1.5' : '';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center '.$habillage]) }}>
    @if ($url)
        <img src="{{ $url }}" alt="{{ $nom }}" class="{{ $hauteur }} w-auto object-contain">
    @else
        <svg class="{{ $hauteur }} w-auto" viewBox="0 0 48 40" fill="none" aria-label="{{ $nom }}" role="img">
            {{-- Plateau de la toque --}}
            <path d="M24 4 46 13.5 24 23 2 13.5 24 4Z"
                  fill="{{ $sombre ? '#43a7e0' : '#1c75bc' }}"/>
            {{-- Corps, en retrait sous le plateau --}}
            <path d="M11 18.5v8.8c0 3.6 5.8 6.5 13 6.5s13-2.9 13-6.5v-8.8L24 24.8 11 18.5Z"
                  fill="{{ $sombre ? '#7fc4ec' : '#1a4f7e' }}"/>
            {{-- Gland, dans l'orange de la charte --}}
            <path d="M43.5 15v9" stroke="#f7941e" stroke-width="2.2" stroke-linecap="round"/>
            <circle cx="43.5" cy="26.5" r="2.8" fill="#f7941e"/>
        </svg>
    @endif
</span>
