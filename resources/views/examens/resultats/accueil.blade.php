@extends('layouts.public')

@section('titre', 'Résultats des examens')

@section('contenu')

<div class="mx-auto max-w-3xl px-4 py-10">

    <div class="text-center">
        <x-logo class="mx-auto h-12 w-auto"/>
        <h1 class="mt-5 text-2xl font-bold text-gris-900">Résultats des examens</h1>
        <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-gris-600">
            Entrez votre numéro de candidat et votre date de naissance. Le résultat s’affiche
            immédiatement : il est préparé à l’avance, et ne dépend pas du nombre de personnes
            connectées en même temps.
        </p>
    </div>

    @if ($examens->isEmpty())
        <div class="carte mt-8 p-8 text-center">
            <h2 class="text-base font-semibold text-gris-900">Aucune session proclamée</h2>
            <p class="mx-auto mt-2 max-w-lg text-sm text-gris-600">
                Les résultats paraissent ici dès la proclamation officielle. D’ici là, rien à consulter.
            </p>
        </div>
    @else
        <div class="mt-8 space-y-4">
            @foreach ($examens as $ligne)
                @php($examen = $ligne['examen'])
                @php($resume = $ligne['resume'])

                <div class="carte overflow-hidden">
                    <div class="carte-entete">
                        <div>
                            <h2 class="text-sm font-semibold text-gris-900">{{ $examen->intitule_complet }}</h2>
                            <p class="mt-0.5 text-xs text-gris-400">
                                {{ $examen->libelle_type }} · session {{ $examen->annee }}
                                @if ($examen->proclamation)
                                    · proclamé le {{ $examen->proclamation->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($resume)
                        <dl class="grid gap-px border-b border-gris-100 bg-gris-100 sm:grid-cols-3">
                            <div class="bg-white px-5 py-3">
                                <dt class="text-[11px] uppercase tracking-wide text-gris-500">Candidats</dt>
                                <dd class="mt-0.5 text-lg font-bold tabular-nums text-gris-900">
                                    {{ number_format($resume['candidats'], 0, ',', ' ') }}
                                </dd>
                            </div>
                            <div class="bg-white px-5 py-3">
                                <dt class="text-[11px] uppercase tracking-wide text-gris-500">Admis</dt>
                                <dd class="mt-0.5 text-lg font-bold tabular-nums text-emerald-700">
                                    {{ number_format($resume['bilan']['admis'] ?? 0, 0, ',', ' ') }}
                                </dd>
                            </div>
                            <div class="bg-white px-5 py-3">
                                <dt class="text-[11px] uppercase tracking-wide text-gris-500">Taux de réussite</dt>
                                <dd class="mt-0.5 text-lg font-bold tabular-nums text-gris-900">
                                    {{ number_format($resume['taux_reussite'] ?? 0, 2, ',', ' ') }} %
                                </dd>
                            </div>
                        </dl>
                    @endif

                    <form method="POST" action="{{ route('resultats.chercher', $examen) }}" class="p-5">
                        @csrf

                        <div class="grid gap-3 sm:grid-cols-[1.2fr_1fr_auto] sm:items-end">
                            <div>
                                <label for="numero-{{ $examen->id }}"
                                       class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                                    Numéro de candidat
                                </label>
                                <input type="text" name="numero" id="numero-{{ $examen->id }}" required
                                       value="{{ old('numero') }}" class="champ w-full font-mono text-sm uppercase"
                                       placeholder="BAC26-00417" autocomplete="off">
                            </div>

                            <div>
                                <label for="naissance-{{ $examen->id }}"
                                       class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                                    Date de naissance
                                </label>
                                <input type="date" name="naissance" id="naissance-{{ $examen->id }}"
                                       value="{{ old('naissance') }}" class="champ w-full text-sm">
                            </div>

                            <button type="submit" class="bouton-primaire h-10">Voir mon résultat</button>
                        </div>

                        @error('numero')<p class="mt-2 text-[11px] text-corail-600">{{ $message }}</p>@enderror
                        @error('naissance')<p class="mt-2 text-[11px] text-corail-600">{{ $message }}</p>@enderror

                        <p class="mt-3 text-[11px] text-gris-500">
                            Numéro perdu ?
                            <a href="{{ route('resultats.numero', $examen) }}" class="font-semibold text-ogar-700 hover:underline">
                                Le retrouver avec son nom
                            </a>
                        </p>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    <p class="mt-8 text-center text-[11px] leading-relaxed text-gris-500">
        Les résultats affichés ici sont ceux du procès-verbal signé. En cas de doute, c’est le
        document signé et cacheté par le président du jury qui fait foi.
    </p>
</div>

@endsection
