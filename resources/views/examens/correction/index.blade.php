@extends('layouts.app')

@section('titre', 'Mes corrections')
@section('sous-titre', $convocations->count().' convocation(s)')

@section('contenu')

    @if ($convocations->isEmpty())
        <div class="carte p-8 text-center">
            <h2 class="text-base font-semibold text-gris-900">Aucune copie à corriger</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-gris-600">
                Vous n’êtes convoqué à aucune correction en cours. Les convocations paraissent ici
                dès que la direction des examens les prononce.
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($convocations as $ligne)
                @php($convocation = $ligne['convocation'])
                @php($avancement = $ligne['avancement'])

                <div class="carte overflow-hidden">
                    <div class="carte-entete">
                        <div>
                            <h2 class="text-sm font-semibold text-gris-900">
                                {{ $convocation->epreuve->intitule ?? 'Jury' }}
                                <span class="font-normal text-gris-500">
                                    — {{ $convocation->examen->intitule_complet }}
                                </span>
                            </h2>
                            <p class="mt-0.5 text-xs text-gris-400">
                                {{ $convocation->libelle_role }}
                                @if ($convocation->centre) · {{ $convocation->centre->nom }} @endif
                                @if ($convocation->epreuve) · barème /{{ $convocation->epreuve->bareme }}
                                    · coefficient {{ $convocation->epreuve->coefficient }} @endif
                            </p>
                        </div>

                        <x-puce :couleur="match ($convocation->statut) {
                            'termine' => 'emerald', 'accepte' => 'sky', 'excuse' => 'rose', default => 'slate',
                        }">{{ $convocation->libelle_statut }}</x-puce>
                    </div>

                    <div class="p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-gris-700">
                                <span class="font-semibold tabular-nums text-gris-900">{{ $avancement['notees'] }}</span>
                                copie(s) notée(s) sur {{ $avancement['attendues'] }}
                                @if ($avancement['reste'] > 0)
                                    — <span class="text-soleil-700">{{ $avancement['reste'] }} restante(s)</span>
                                @endif
                            </p>

                            <a href="{{ route('correction.copies', $convocation) }}" class="bouton-primaire">
                                {{ $avancement['reste'] > 0 ? 'Corriger' : 'Revoir les notes' }}
                            </a>
                        </div>

                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-gris-100">
                            <div class="h-full rounded-full {{ $avancement['part'] >= 100 ? 'bg-emerald-500' : 'bg-ogar-500' }}"
                                 style="width: {{ max(2, min(100, $avancement['part'])) }}%"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-6 text-center text-[11px] leading-relaxed text-gris-500">
            Les copies sont désignées par leur numéro d’anonymat : vous ne voyez jamais le nom du
            candidat, et c’est ce qui rend la correction incontestable.
        </p>
    @endif

@endsection
