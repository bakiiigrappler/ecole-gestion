@extends('layouts.app')

@section('titre', $examen->intitule_complet)
@section('sous-titre', $examen->libelle_type.' · session '.$examen->annee.' · '.$examen->libelle_statut)

@section('actions-entete')
    <a href="{{ route('examens.candidats', $examen) }}" class="bouton-secondaire">Candidats</a>
    <a href="{{ route('examens.correcteurs', $examen) }}" class="bouton-secondaire">Correcteurs</a>
    <a href="{{ route('examens.index') }}" class="bouton-primaire">Toutes les sessions</a>
@endsection

@section('contenu')

@php
    $etapes = \App\Models\Examen::ETAPES;
    $rangCourant = $examen->rangDeLEtape();
@endphp

    {{-- ----------------------------------------------------------------
         Le fil de la session : où l'on en est, et ce qui vient
         ---------------------------------------------------------------- --}}
    <div class="carte overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">Le fil de la session</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    Chaque étape n’ouvre que ce que la précédente a rendu possible.
                </p>
            </div>
        </div>

        <ol class="flex flex-wrap gap-2 p-5">
            @foreach ($etapes as $cle => $libelle)
                @php($rang = array_search($cle, array_keys($etapes), true))
                @php($faite = $rang < $rangCourant)
                @php($courante = $cle === $examen->statut)

                <li class="flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm
                           {{ $courante ? 'border-ogar-500 bg-ogar-50 font-semibold text-ogar-800'
                               : ($faite ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                               : 'border-gris-200 text-gris-500') }}">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold
                                 {{ $courante ? 'bg-ogar-600 text-white'
                                     : ($faite ? 'bg-emerald-600 text-white' : 'bg-gris-200 text-gris-600') }}">
                        {{ $rang + 1 }}
                    </span>
                    {{ $libelle }}
                </li>
            @endforeach
        </ol>

        @unless ($examen->estProclame())
            <div class="flex flex-wrap items-center gap-2 border-t border-gris-100 bg-gris-50 px-5 py-3">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">Passer à</span>

                @foreach (['inscriptions', 'epreuves', 'correction'] as $cible)
                    @continue($cible === $examen->statut)
                    <form method="POST" action="{{ route('examens.etape', $examen) }}">
                        @csrf
                        <input type="hidden" name="statut" value="{{ $cible }}">
                        <button type="submit" class="bouton-mini">{{ $etapes[$cible] }}</button>
                    </form>
                @endforeach
            </div>
        @endunless
    </div>

    {{-- Les chiffres de la session --}}
    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Candidats" :valeur="$chiffres['candidats']"
                       :detail="$chiffres['sans_anonymat'] ? $chiffres['sans_anonymat'].' sans numéro d’anonymat' : 'Anonymat tiré'"
                       couleur="ogar"/>
        <x-statistique libelle="Admis" :valeur="$chiffres['admis']"
                       :detail="$chiffres['second_tour'].' au second tour'" couleur="emerald"/>
        <x-statistique libelle="Ajournés" :valeur="$chiffres['ajournes']"
                       detail="Après délibération" couleur="rose"/>
        <x-statistique libelle="Correcteurs" :valeur="$chiffres['correcteurs']"
                       :detail="$chiffres['centres'].' centre(s)'" couleur="violet"/>
    </div>

<div class="mt-4 grid gap-6 lg:grid-cols-3">

    <div class="space-y-4 lg:col-span-2">

        {{-- ------------------------------------------------------------
             Les épreuves
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Les épreuves</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Total des coefficients : {{ rtrim(rtrim(number_format($examen->totalDesCoefficients(), 1, ',', ' '), '0'), ',') }}
                        — les facultatives n’y entrent pas.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Épreuve</th>
                            <th class="text-center">Coef.</th>
                            <th class="text-center">Barème</th>
                            <th class="text-center">Durée</th>
                            <th class="text-center">Correction</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($examen->epreuves as $i => $epreuve)
                            @php($etat = $avancement[$i] ?? null)
                            <tr>
                                <td class="font-medium text-gris-800">
                                    {{ $epreuve->intitule }}
                                    @if ($epreuve->facultative)
                                        <x-puce couleur="slate">Facultative</x-puce>
                                    @endif
                                    @if ($epreuve->date_epreuve)
                                        <span class="block text-[11px] text-gris-400">
                                            {{ $epreuve->date_epreuve->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center tabular-nums text-gris-700">{{ $epreuve->coefficient }}</td>
                                <td class="text-center tabular-nums text-gris-500">/{{ $epreuve->bareme }}</td>
                                <td class="text-center text-[11px] text-gris-500">{{ $epreuve->libelle_duree ?? '—' }}</td>

                                <td class="text-center">
                                    @if ($etat)
                                        <span class="text-[11px] tabular-nums text-gris-600">
                                            {{ $etat['saisies'] }} / {{ $etat['attendues'] }}
                                        </span>
                                        <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-gris-100">
                                            <span class="block h-full rounded-full {{ $etat['part'] >= 100 ? 'bg-emerald-500' : 'bg-ogar-500' }}"
                                                  style="width: {{ min(100, $etat['part']) }}%"></span>
                                        </span>
                                    @endif
                                </td>

                                <td class="text-right">
                                    @unless ($examen->auMoins('correction'))
                                        <x-confirmation :action="route('examens.epreuves.supprimer', [$examen, $epreuve])"
                                                        methode="DELETE"
                                                        titre="Retirer cette épreuve ?"
                                                        :message="$epreuve->intitule.' sera retirée de la session.'"
                                                        confirmer="Retirer"
                                                        bouton="bouton-mini text-corail-600">
                                            Retirer
                                        </x-confirmation>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="6" message="Aucune épreuve : déclarez-les avant d’inscrire."/>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @unless ($examen->auMoins('correction'))
                <form method="POST" action="{{ route('examens.epreuves.ajouter', $examen) }}"
                      class="flex flex-wrap items-end gap-3 border-t border-gris-100 bg-gris-50 p-5">
                    @csrf

                    <div class="min-w-48 flex-1">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Intitulé
                        </label>
                        <input type="text" name="intitule" required class="champ w-full text-sm" placeholder="Mathématiques">
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Coef.</label>
                        <input type="number" step="0.5" min="0.5" max="20" name="coefficient" value="1" required
                               class="champ w-20 text-sm tabular-nums">
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Barème</label>
                        <input type="number" min="1" max="100" name="bareme" value="20" required
                               class="champ w-20 text-sm tabular-nums">
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Durée (min)</label>
                        <input type="number" min="15" max="480" name="duree_minutes" class="champ w-24 text-sm tabular-nums">
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Date</label>
                        <input type="date" name="date_epreuve" class="champ w-40 text-sm">
                    </div>

                    <label class="flex items-center gap-2 pb-2 text-sm text-gris-700">
                        <input type="checkbox" name="facultative" value="1">
                        Facultative
                    </label>

                    <button type="submit" class="bouton-secondaire">Ajouter</button>
                </form>
            @endunless
        </div>

        {{-- ------------------------------------------------------------
             Les centres
             ------------------------------------------------------------ --}}
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Les centres de composition</h2>
                <span class="text-xs text-gris-400">{{ $examen->centres->count() }} centre(s)</span>
            </div>

            <ul class="divide-y divide-gris-100">
                @forelse ($examen->centres as $centre)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gris-900">
                                {{ $centre->nom }}
                                @if ($centre->code)
                                    <span class="font-mono text-[11px] text-gris-400">{{ $centre->code }}</span>
                                @endif
                            </p>
                            <p class="text-[11px] text-gris-500">
                                @if ($centre->ville) {{ $centre->ville }} · @endif
                                {{ $centre->candidats()->count() }} candidat(s)
                                @if ($centre->president_jury) · jury : {{ $centre->president_jury }} @endif
                            </p>
                        </div>

                        @if ($centre->capacite)
                            <x-puce :couleur="$centre->places_libres > 0 ? 'emerald' : 'rose'">
                                {{ $centre->places_libres > 0 ? $centre->places_libres.' place(s)' : 'Complet' }}
                            </x-puce>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-gris-500">
                        Aucun centre déclaré : les candidats n’auraient nulle part où composer.
                    </li>
                @endforelse
            </ul>

            <form method="POST" action="{{ route('examens.centres.ajouter', $examen) }}"
                  class="flex flex-wrap items-end gap-3 border-t border-gris-100 bg-gris-50 p-5">
                @csrf

                <div class="min-w-48 flex-1">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Nom</label>
                    <input type="text" name="nom" required class="champ w-full text-sm" placeholder="Lycée National Léon Mba">
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Code</label>
                    <input type="text" name="code" class="champ w-24 text-sm" placeholder="C01">
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Ville</label>
                    <input type="text" name="ville" class="champ w-36 text-sm">
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">Capacité</label>
                    <input type="number" min="1" name="capacite" class="champ w-24 text-sm tabular-nums">
                </div>

                <button type="submit" class="bouton-secondaire">Ajouter</button>
            </form>
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Colonne de droite : les actes de la session
         ------------------------------------------------------------------ --}}
    <div class="space-y-4">

        @if ($chiffres['sans_anonymat'] > 0 && $examen->auMoins('epreuves'))
            <div class="carte border-soleil-300 p-5">
                <h2 class="text-sm font-semibold text-soleil-900">Anonymat à tirer</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-gris-700">
                    {{ $chiffres['sans_anonymat'] }} candidat(s) n’ont pas de numéro d’anonymat. Sans lui, le
                    correcteur verrait les noms — et la correction serait contestable.
                </p>
                <form method="POST" action="{{ route('examens.anonymat', $examen) }}" class="mt-3">
                    @csrf
                    <button type="submit" class="bouton-primaire w-full justify-center">Tirer les numéros</button>
                </form>
            </div>
        @endif

        @if ($examen->auMoins('correction'))
            <div class="carte p-5">
                <h2 class="text-sm font-semibold text-gris-900">Délibération</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-gris-600">
                    Calcule la moyenne de chaque candidat, prononce l’admission, le second tour ou
                    l’ajournement, et attribue les mentions. Peut se relancer autant de fois que
                    nécessaire tant que le procès-verbal n’est pas signé.
                </p>
                <form method="POST" action="{{ route('examens.deliberer', $examen) }}" class="mt-3">
                    @csrf
                    <button type="submit" class="bouton-primaire w-full justify-center">Prononcer la délibération</button>
                </form>
            </div>
        @endif

        {{-- La signature : scellement ou dépôt du scan --}}
        @if ($examen->auMoins('deliberation'))
            <form method="POST" action="{{ route('examens.signer', $examen) }}" enctype="multipart/form-data"
                  x-data="{ mode: 'electronique' }" class="carte overflow-hidden">
                @csrf

                <div class="carte-entete">
                    <div>
                        <h2 class="text-sm font-semibold text-gris-900">Procès-verbal</h2>
                        <p class="mt-0.5 text-xs text-gris-400">Signez-le, ou déposez-le signé à la main.</p>
                    </div>
                </div>

                {{-- On ne signe pas un enregistrement : on signe un document.
                     Il faut pouvoir le lire avant. --}}
                <a href="{{ route('examens.proces-verbal', $examen) }}"
                   class="block border-b border-gris-100 bg-gris-50 px-5 py-3 text-sm font-semibold
                          text-ogar-700 hover:bg-gris-100">
                    Lire le procès-verbal avant de signer →
                </a>

                <div class="space-y-3 p-5">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="mode_signature" value="electronique" class="peer sr-only" x-model="mode">
                            <span class="block rounded-xl border border-gris-200 px-3 py-2 text-center text-sm
                                         transition peer-checked:border-ogar-500 peer-checked:bg-ogar-50">
                                Signature électronique
                            </span>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="mode_signature" value="manuscrite" class="peer sr-only" x-model="mode">
                            <span class="block rounded-xl border border-gris-200 px-3 py-2 text-center text-sm
                                         transition peer-checked:border-ogar-500 peer-checked:bg-ogar-50">
                                Signé à la main
                            </span>
                        </label>
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Centre
                        </label>
                        <select name="centre_id" class="champ w-full text-sm">
                            <option value="">Toute la session</option>
                            @foreach ($examen->centres as $centre)
                                <option value="{{ $centre->id }}">{{ $centre->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Signataire <span class="text-corail-600">*</span>
                        </label>
                        <input type="text" name="signataire_nom" required class="champ w-full text-sm"
                               value="{{ old('signataire_nom', auth()->user()->name) }}">
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Qualité <span class="text-corail-600">*</span>
                        </label>
                        <input type="text" name="signataire_qualite" required class="champ w-full text-sm"
                               value="{{ old('signataire_qualite', 'Président du jury') }}">
                    </div>

                    <div x-show="mode === 'manuscrite'" x-cloak>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Procès-verbal signé et cacheté
                        </label>
                        <input type="file" name="fichier" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs">
                        <p class="mt-1 text-[11px] text-gris-400">PDF ou photo, 8 Mo au plus.</p>
                        @error('fichier')<p class="mt-1 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                    </div>

                    <p class="text-[11px] leading-relaxed text-gris-500" x-show="mode === 'electronique'" x-cloak>
                        Le scellement enregistre l’empreinte des résultats, votre identité et l’heure.
                        Toute note modifiée ensuite casse l’empreinte, et la vérification le dira.
                    </p>
                </div>

                <div class="border-t border-gris-100 p-5">
                    <button type="submit" class="bouton-primaire w-full justify-center">Signer le procès-verbal</button>
                </div>
            </form>
        @endif

        {{-- Les documents signés --}}
        @if ($documents->isNotEmpty())
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <h2 class="text-sm font-semibold text-gris-900">Documents signés</h2>
                </div>

                <ul class="divide-y divide-gris-100">
                    @foreach ($documents as $document)
                        <li class="px-5 py-3">
                            <p class="text-sm font-medium text-gris-900">{{ $document->libelle_type }}</p>
                            <p class="text-[11px] text-gris-500">
                                {{ $document->signataire_nom }} · {{ $document->signataire_qualite }}
                                @if ($document->signe_le) · {{ $document->signe_le->format('d/m/Y à H:i') }} @endif
                            </p>
                            <p class="mt-1 flex flex-wrap items-center gap-2 text-[11px]">
                                <x-puce :couleur="$document->estIntact() ? 'emerald' : 'rose'">
                                    {{ $document->estIntact() ? 'Intact' : 'Résultats modifiés depuis' }}
                                </x-puce>
                                <span class="text-gris-400">{{ $document->libelle_mode }}</span>
                                @if ($document->jeton_verification)
                                    <span class="font-mono text-gris-400">{{ $document->jeton_verification }}</span>
                                @endif
                            </p>

                            <div class="mt-2 flex flex-wrap gap-1">
                                <a href="{{ route('examens.proces-verbal', array_filter([
                                        'examen' => $examen->id, 'centre' => $document->centre_id,
                                    ])) }}" class="bouton-mini">Relire</a>

                                @if ($document->fichier)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($document->fichier) }}"
                                       target="_blank" rel="noopener" class="bouton-mini">
                                        Le document déposé
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                @unless ($examen->estProclame())
                    <form method="POST" action="{{ route('examens.proclamer', $examen) }}"
                          class="border-t border-gris-100 bg-gris-50 p-5">
                        @csrf
                        <button type="submit" class="bouton-primaire w-full justify-center">Proclamer les résultats</button>
                        <p class="mt-2 text-[11px] leading-relaxed text-gris-500">
                            La proclamation écrit une fiche par candidat, servie ensuite sans toucher la base.
                            C’est ce qui permet au portail de tenir le jour J.
                        </p>
                    </form>
                @else
                    <p class="border-t border-gris-100 bg-emerald-50 px-5 py-3 text-[11px] text-emerald-800">
                        Résultats proclamés le {{ optional($examen->proclamation)->format('d/m/Y') }} —
                        <a href="{{ route('resultats.accueil') }}" target="_blank" rel="noopener" class="font-semibold hover:underline">
                            voir la page publique
                        </a>
                    </p>
                @endunless
            </div>
        @endif
    </div>
</div>

@endsection
