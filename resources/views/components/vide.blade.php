@props([
    'message' => 'Aucun élément à afficher.',
    'colonnes' => null,
    'mascotte' => true,
])

{{--
    État vide. Dans un tableau, `colonnes` donne le nombre de colonnes à
    couvrir ; ailleurs le composant se suffit à lui-même.

    La mascotte n'apparaît que si la place le permet : elle est masquée sous
    `sm` pour ne pas alourdir l'affichage sur un téléphone.
--}}

@if ($colonnes)
    <tr>
        <td colspan="{{ $colonnes }}" class="px-4 py-10 text-center text-sm text-gris-400">
            @if ($mascotte)
                <div class="flex flex-col items-center gap-3">
                    <x-mascotte pose="vide" taille="h-20" class="hidden sm:flex"/>
                    <span>{{ $message }}</span>
                </div>
            @else
                {{ $message }}
            @endif
        </td>
    </tr>
@else
    <div class="px-4 py-10 text-center text-sm text-gris-400">
        @if ($mascotte)
            <div class="flex flex-col items-center gap-3">
                <x-mascotte pose="vide" taille="h-20" class="hidden sm:flex"/>
                <span>{{ $message }}</span>
            </div>
        @else
            {{ $message }}
        @endif
    </div>
@endif
