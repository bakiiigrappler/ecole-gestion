@extends('layouts.public')

@section('titre', 'Retrouver son numéro')

@section('contenu')

<div class="mx-auto max-w-2xl px-4 py-10">

    <div class="text-center">
        <h1 class="text-xl font-bold text-gris-900">Retrouver son numéro de candidat</h1>
        <p class="mx-auto mt-2 max-w-lg text-sm leading-relaxed text-gris-600">
            {{ $examen->intitule_complet }}. Entrez votre nom tel qu’il figure sur la convocation ;
            la liste ci-dessous vous rendra votre numéro.
        </p>
    </div>

    <form method="GET" action="{{ route('resultats.numero', $examen) }}" class="carte mt-6 p-5">
        <label for="nom" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
            Nom et prénom
        </label>

        <div class="flex flex-wrap gap-2">
            <input type="search" name="nom" id="nom" value="{{ $terme }}" required minlength="3"
                   class="champ min-w-48 flex-1 text-sm" placeholder="Au moins trois lettres" autocomplete="off">
            <button type="submit" class="bouton-primaire">Chercher</button>
        </div>

        <p class="mt-2 text-[11px] text-gris-500">
            La recherche porte sur la liste publiée à la proclamation : elle ne consulte aucun dossier.
        </p>
    </form>

    @if ($terme !== '' && mb_strlen($terme) >= 3)
        <div class="carte mt-4 overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">
                    {{ count($trouves) }} résultat(s) pour « {{ $terme }} »
                </h2>
            </div>

            <ul class="divide-y divide-gris-100">
                @forelse ($trouves as $trouve)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                        <span class="text-sm capitalize text-gris-800">{{ $trouve['nom'] }}</span>

                        <form method="POST" action="{{ route('resultats.chercher', $examen) }}"
                              class="flex flex-wrap items-center gap-2">
                            @csrf
                            <input type="hidden" name="numero" value="{{ $trouve['numero'] }}">
                            <span class="font-mono text-sm font-semibold text-gris-900">{{ $trouve['numero'] }}</span>
                            <input type="date" name="naissance" required class="champ w-40 text-sm"
                                   aria-label="Date de naissance">
                            <button type="submit" class="bouton-mini">Voir</button>
                        </form>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gris-500">
                        Aucun nom ne correspond. Vérifiez l’orthographe, ou essayez seulement le nom de famille.
                    </li>
                @endforelse
            </ul>
        </div>
    @endif

    <div class="mt-6 text-center">
        <a href="{{ route('resultats.accueil') }}" class="bouton-secondaire">Retour aux résultats</a>
    </div>
</div>

@endsection
