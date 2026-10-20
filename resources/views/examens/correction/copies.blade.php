@extends('layouts.app')

@section('titre', $epreuve->intitule)
@section('sous-titre', $examen->intitule_complet
    .($convocation->centre ? ' · '.$convocation->centre->nom : '')
    .' · barème /'.$epreuve->bareme)

@section('actions-entete')
    <a href="{{ route('correction.index') }}" class="bouton-secondaire">Mes corrections</a>
@endsection

@section('contenu')

    {{-- L'anonymat, rappelé là où il s'applique --}}
    <div class="carte mb-4 border-ogar-200 bg-ogar-50 p-4">
        <p class="text-sm leading-relaxed text-gris-700">
            Les copies sont désignées par leur <strong class="text-gris-900">numéro d’anonymat</strong>.
            Vous ne verrez pas le nom des candidats : c’est ce qui rend votre correction incontestable.
            Les notes se saisissent sur <strong class="text-gris-900">{{ $epreuve->bareme }}</strong>,
            et l’application les ramènera sur 20 au moment de la délibération.
        </p>
    </div>

    <div class="carte mb-4 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gris-700">
                <span class="font-semibold tabular-nums text-gris-900">{{ $avancement['notees'] }}</span>
                sur {{ $avancement['attendues'] }} copie(s) notée(s)
            </p>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('correction.copies', $convocation) }}"
                   class="{{ $resteSeulement ? 'bouton-secondaire' : 'bouton-mini' }}">Toutes</a>
                <a href="{{ route('correction.copies', [$convocation, 'reste' => 1]) }}"
                   class="{{ $resteSeulement ? 'bouton-mini' : 'bouton-secondaire' }}">
                    Les non notées ({{ $avancement['reste'] }})
                </a>
            </div>
        </div>

        <div class="mt-3 h-2 overflow-hidden rounded-full bg-gris-100">
            <div class="h-full rounded-full {{ $avancement['part'] >= 100 ? 'bg-emerald-500' : 'bg-ogar-500' }}"
                 style="width: {{ max(2, min(100, $avancement['part'])) }}%"></div>
        </div>
    </div>

    @if (! $examen->accepteLesNotes())
        <div class="carte border-soleil-300 bg-soleil-50 p-5">
            <h2 class="text-sm font-semibold text-soleil-900">Saisie fermée</h2>
            <p class="mt-1.5 text-sm text-gris-700">
                La session est en « {{ $examen->libelle_statut }} » : les notes ne se modifient plus.
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('correction.enregistrer', $convocation) }}">
        @csrf

        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Le paquet</h2>
                <span class="text-xs text-gris-400">{{ $copies->count() }} copie(s) affichée(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Anonymat</th>
                            <th class="w-40">Note sur {{ $epreuve->bareme }}</th>
                            <th class="w-28 text-center">Absent</th>
                            <th class="text-center">Saisie</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($copies as $copie)
                            @php($note = $notes->get($copie->id))

                            <tr>
                                <td class="font-mono text-sm font-semibold text-gris-900">
                                    {{ $copie->numero_anonymat }}
                                </td>

                                <td>
                                    <input type="number" step="0.25" min="0" max="{{ $epreuve->bareme }}"
                                           name="notes[{{ $copie->id }}]"
                                           value="{{ $note && ! $note->absent ? rtrim(rtrim(number_format($note->note, 2, '.', ''), '0'), '.') : '' }}"
                                           class="champ w-28 text-sm tabular-nums"
                                           @disabled(! $examen->accepteLesNotes())
                                           aria-label="Note de la copie {{ $copie->numero_anonymat }}">
                                </td>

                                <td class="text-center">
                                    <input type="checkbox" name="absents[{{ $copie->id }}]" value="1"
                                           @checked($note?->absent)
                                           @disabled(! $examen->accepteLesNotes())
                                           aria-label="Copie absente">
                                </td>

                                <td class="text-center text-[11px] text-gris-400">
                                    @if ($note?->saisie_le)
                                        {{ $note->saisie_le->format('d/m/Y à H:i') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="4"
                                    :message="$resteSeulement
                                        ? 'Toutes les copies de ce paquet sont notées.'
                                        : 'Aucune copie : les numéros d’anonymat n’ont pas encore été tirés.'"/>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($copies->isNotEmpty() && $examen->accepteLesNotes())
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gris-100 bg-gris-50 p-5">
                    <p class="text-[11px] leading-relaxed text-gris-500">
                        Remplissez la colonne, puis enregistrez une fois : trente copies en une seule fois,
                        pas trente allers-retours.
                    </p>
                    <button type="submit" class="bouton-primaire">Enregistrer les notes</button>
                </div>
            @endif
        </div>
    </form>

    @if ($avancement['reste'] === 0 && $convocation->statut !== 'termine' && $examen->accepteLesNotes())
        <form method="POST" action="{{ route('correction.terminer', $convocation) }}" class="mt-4">
            @csrf
            <button type="submit" class="bouton-primaire w-full justify-center">
                Déclarer ma correction terminée
            </button>
            <p class="mt-2 text-center text-[11px] text-gris-500">
                La direction des examens saura que votre paquet est fini.
            </p>
        </form>
    @endif

@endsection
