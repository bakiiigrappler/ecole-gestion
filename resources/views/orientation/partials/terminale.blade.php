{{--
    Le choix d'après la terminale : une filière du supérieur.

    La série du bac commande ce qui s'ouvre — elle est lue sur la classe, l'élève
    n'a rien à déclarer. Les métiers figurent à côté des filières : c'est la
    question que l'élève pose vraiment, et à laquelle un nom de licence ne
    répond pas.
--}}

@php
    $filieresSuperieur = \App\Support\Orientation\VoiesApresTerminale::filieres($serie);
    $moyenne = $profil['generale'];
@endphp

<div class="carte overflow-hidden">
    <div class="carte-entete">
        <div>
            <h2 class="text-sm font-semibold text-gris-900">
                {{ \App\Support\Orientation\VoiesApresTerminale::libelle($serie) }}
            </h2>
            <p class="mt-0.5 text-xs text-gris-400">
                Lue sur votre classe — c’est elle qui commande l’entrée dans le supérieur.
            </p>
        </div>
    </div>

    @if ($moyenne !== null)
        <p class="border-b border-gris-100 px-5 py-3 text-sm leading-relaxed text-gris-700">
            {{ \App\Support\Orientation\VoiesApresTerminale::mentionAttendue($moyenne) }}
        </p>
    @endif

    @if (! $serie)
        <div class="p-8 text-center text-sm text-gris-500">
            Aucune série n’est rattachée à votre classe : le secrétariat doit la renseigner pour que
            les filières du supérieur s’affichent.
        </div>
    @else
        <div class="divide-y divide-gris-100">
            @foreach ($filieresSuperieur as $filiere)
                <label class="flex cursor-pointer items-start gap-3 px-5 py-3 transition hover:bg-gris-50">
                    <input type="radio" name="filiere" value="{{ $filiere }}" class="mt-1 shrink-0"
                           @checked(old('filiere', $dossier?->filiere) === $filiere)
                           @disabled($fige)>
                    <span class="text-sm text-gris-900">{{ $filiere }}</span>
                </label>
            @endforeach
        </div>

        <div class="border-t border-gris-100 bg-gris-50 px-5 py-3">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Métiers au bout</p>
            <p class="mt-1 text-sm leading-relaxed text-gris-700">
                {{ \App\Support\Orientation\VoiesApresTerminale::metiers($serie) }}
            </p>
        </div>
    @endif
</div>
