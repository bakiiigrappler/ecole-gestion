@props(['personne' => null, 'nom' => null, 'taille' => 'h-9 w-9'])

{{--
    Photo d'un élève ou d'un enseignant, avec repli sur ses initiales.

    Le modèle passé en `personne` expose `photo` et `full_name` ; `nom` sert aux
    cas où seule une chaîne est disponible (un utilisateur, par exemple).
--}}

@php
    $libelle = $nom ?? $personne?->full_name ?? '';

    $initiales = collect(preg_split('/\s+/', trim($libelle)))
        ->filter()
        ->take(2)
        ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
        ->implode('') ?: '??';
@endphp

@if ($personne?->photo)
    <img src="{{ asset('storage/'.$personne->photo) }}"
         alt="{{ $libelle }}"
         class="{{ $taille }} shrink-0 rounded-full object-cover ring-1 ring-gris-200">
@else
    <span class="{{ $taille }} flex shrink-0 items-center justify-center rounded-full bg-ogar-100 text-xs font-bold text-ogar-800 ring-1 ring-ogar-200"
          title="{{ $libelle }}">
        {{ $initiales }}
    </span>
@endif
