@props(['couleur' => 'slate'])

@php
    // Contrastes vérifiés pour rester au-dessus de 4.5:1 sur fond clair.
    $styles = [
        'emerald' => 'bg-emerald-100 text-emerald-800',
        'amber'   => 'bg-soleil-100 text-soleil-800',
        'sky'     => 'bg-ogar-100 text-ogar-800',
        'rose'    => 'bg-corail-100 text-corail-800',
        'violet'  => 'bg-ardoise-900/10 text-ardoise-900',
        'ogar'    => 'bg-ogar-600 text-white',
        'slate'   => 'bg-gris-100 text-gris-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'puce '.($styles[$couleur] ?? $styles['slate'])]) }}>
    {{ $slot }}
</span>
