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

{{-- L'ANBG : le vœu fait ici ne remplace pas l'inscription nationale --}}
<div class="carte overflow-hidden border-ogar-200">
    <div class="flex items-start gap-3 border-b border-ogar-200 bg-ogar-50 px-5 py-3">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-ogar-600" fill="none" stroke="currentColor" stroke-width="1.7"
             viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
        </svg>
        <div>
            <h2 class="text-sm font-semibold text-ogar-900">Agence Nationale des Bourses du Gabon</h2>
            <p class="mt-0.5 text-[11px] text-gris-600">
                L’inscription nationale et la demande de bourse passent par elle.
            </p>
        </div>
    </div>

    <div class="p-5">
        <p class="text-sm leading-relaxed text-gris-700">
            Le vœu posé ici est celui que votre établissement étudie. Il ne remplace pas l’inscription
            auprès de l’<strong class="text-gris-900">ANBG</strong>, par laquelle passent l’orientation
            nationale, les demandes de bourse et les dossiers de formation à l’étranger. Les deux
            démarches se mènent de front, et celle de l’ANBG a ses propres dates.
        </p>

        <a href="https://anbg.online/accueil" target="_blank" rel="noopener noreferrer"
           class="bouton-primaire mt-4">
            Ouvrir le site de l’ANBG
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
            </svg>
        </a>

        <p class="mt-2 text-[11px] text-gris-500">anbg.online — s’ouvre dans un nouvel onglet.</p>
    </div>
</div>
