@props([
    'libelle',
    'valeur',
    'detail' => null,
    'couleur' => 'slate',
    'lien' => null,
])

@php
    // Palette alignée sur la charte : bleu institutionnel, orange et rouge du logotype,
    // complétée par deux teintes de service pour les statuts positifs et informatifs.
    $fonds = [
        'ogar'    => 'from-ogar-700 to-ogar-500',
        'sky'     => 'from-ogar-600 to-ogar-400',
        'amber'   => 'from-soleil-600 to-soleil-400',
        'rose'    => 'from-corail-700 to-corail-500',
        'emerald' => 'from-emerald-700 to-emerald-500',
        'violet'  => 'from-ardoise-800 to-ogar-700',
        'slate'   => 'from-gris-700 to-gris-500',
    ];
    $classes = 'block overflow-hidden rounded-xl bg-gradient-to-br '
        .($fonds[$couleur] ?? $fonds['slate'])
        .' p-4 text-white shadow-sm transition-shadow duration-200'
        .($lien ? ' cursor-pointer hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-600 focus-visible:ring-offset-2' : '');
@endphp

@if ($lien)
    <a href="{{ $lien }}" class="{{ $classes }}">
@else
    <div class="{{ $classes }}">
@endif

    <div class="text-[11px] font-semibold uppercase tracking-wider text-white/85">{{ $libelle }}</div>
    <div class="mt-2 text-3xl font-bold leading-none">{{ $valeur }}</div>
    @if ($detail)
        <div class="mt-2 text-xs text-white/80">{{ $detail }}</div>
    @endif

@if ($lien)
    </a>
@else
    </div>
@endif
