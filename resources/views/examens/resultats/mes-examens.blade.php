@extends('layouts.app')

@section('titre', auth()->user()?->role === 'parent' ? 'Examens de mes enfants' : 'Mes examens')
@section('sous-titre', $candidatures->count().' candidature(s)')

@section('contenu')

    @if ($candidatures->isEmpty())
        <div class="carte p-8 text-center">
            <h2 class="text-base font-semibold text-gris-900">Aucune candidature</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-gris-600">
                {{ auth()->user()?->role === 'parent' ? 'Aucun de vos enfants' : 'Vous n’êtes' }}
                n’est inscrit à un examen national. L’inscription est faite par l’établissement,
                à partir de la classe.
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($candidatures as $candidat)
                @php($examen = $candidat->examen)
                @php($proclame = $examen->estProclame())
                @php($admis = $candidat->statut === 'admis')

                <div class="carte overflow-hidden">
                    <div class="carte-entete">
                        <div>
                            <h2 class="text-sm font-semibold text-gris-900">{{ $examen->intitule_complet }}</h2>
                            <p class="mt-0.5 text-xs text-gris-400">
                                {{ $candidat->nom_complet }} ·
                                <span class="font-mono">{{ $candidat->numero_candidat }}</span>
                                @if ($candidat->centre) · {{ $candidat->centre->nom }} @endif
                            </p>
                        </div>

                        <x-puce :couleur="$proclame
                            ? ($admis ? 'emerald' : ($candidat->statut === 'second_tour' ? 'amber' : 'rose'))
                            : 'slate'">
                            {{ $proclame ? $candidat->libelle_statut : $examen->libelle_statut }}
                        </x-puce>
                    </div>

                    <div class="p-5">
                        @if ($proclame)
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-gris-500">Moyenne générale</p>
                                    <p class="text-2xl font-bold tabular-nums text-gris-900">
                                        {{ $candidat->moyenne !== null ? number_format($candidat->moyenne, 2, ',', ' ') : '—' }}
                                        <span class="text-sm font-normal text-gris-400">/20</span>
                                    </p>
                                    @if ($candidat->mention)
                                        <p class="text-[11px] font-semibold text-gris-700">
                                            Mention {{ $candidat->mention }}
                                        </p>
                                    @endif
                                </div>

                                <a href="{{ route('examens.releve', $candidat) }}" class="bouton-primaire">
                                    Le relevé de notes
                                </a>
                            </div>
                        @else
                            <p class="text-sm leading-relaxed text-gris-600">
                                Les résultats ne sont pas encore proclamés. Le centre de composition est
                                <strong class="text-gris-800">{{ $candidat->centre->nom ?? 'à préciser' }}</strong>
                                @if ($candidat->centre?->ville) ({{ $candidat->centre->ville }}) @endif.
                                @if ($examen->debut_epreuves)
                                    Les épreuves commencent le
                                    {{ $examen->debut_epreuves->format('d/m/Y') }}.
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

@endsection
