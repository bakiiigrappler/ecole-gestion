@extends('layouts.app')

@section('titre', 'Mes élèves')
@section('sous-titre', $enseignant
    ? $eleves->flatten()->count().' élève(s) répartis sur '.$classes->count().' classe(s)'
    : 'Aucune fiche enseignant rattachée à ce compte')

@section('actions-entete')
    @if ($ouverte)
        <a href="{{ route('classes.fiche', $ouverte) }}" class="bouton-secondaire">Fiche de classe</a>
    @endif
    <a href="{{ route('classes.index') }}" class="bouton-primaire">Mes classes</a>
@endsection

@section('contenu')

@php
    $sexe = fn ($eleve) => match (mb_strtolower((string) $eleve->gender)) {
        'm', 'male', 'masculin', 'garcon', 'garçon' => 'M',
        'f', 'female', 'feminin', 'féminin', 'fille' => 'F',
        default => '—',
    };
@endphp

    @unless ($enseignant)
        <div class="carte p-8">
            <x-vide message="Ce compte n’est rattaché à aucune fiche enseignant."/>
        </div>
    @elseif ($classes->isEmpty())
        <div class="carte p-8">
            <x-vide message="Aucune classe ne vous est attribuée cette année."/>
        </div>
    @else

    {{-- ----------------------------------------------------------------
         Un onglet par classe : c'est ainsi qu'un enseignant cherche.
         ---------------------------------------------------------------- --}}
    <div class="carte overflow-hidden">
        <div class="flex flex-wrap gap-1 border-b border-gris-100 bg-gris-50 px-3 pt-3">
            @foreach ($classes as $classe)
                @php($nombre = ($eleves[$classe->id] ?? collect())->count())

                <a href="{{ route('students.index', array_filter(['class' => $classe->id, 'search' => $terme ?: null])) }}"
                   class="flex items-center gap-2 rounded-t-lg border border-b-0 px-4 py-2 text-sm font-medium transition
                          {{ (int) $ouverte === (int) $classe->id
                             ? 'border-gris-200 bg-white text-ogar-700'
                             : 'border-transparent text-gris-500 hover:text-gris-800' }}">
                    {{ $classe->name }}
                    <span class="rounded-full px-2 py-0.5 text-[11px] tabular-nums
                                 {{ (int) $ouverte === (int) $classe->id ? 'bg-ogar-50 text-ogar-700' : 'bg-gris-200 text-gris-600' }}">
                        {{ $nombre }}
                    </span>
                </a>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gris-100 px-5 py-3">
            <form method="GET" action="{{ route('students.index') }}" class="flex items-end gap-2">
                <input type="hidden" name="class" value="{{ $ouverte }}">
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Rechercher
                    </label>
                    <input type="text" name="search" value="{{ $terme }}"
                           placeholder="Nom, prénom ou matricule" class="champ w-64 text-sm">
                </div>
                <button type="submit" class="bouton-secondaire mb-0.5">Chercher</button>
                @if ($terme !== '')
                    <a href="{{ route('students.index', ['class' => $ouverte]) }}"
                       class="bouton-secondaire mb-0.5">Effacer</a>
                @endif
            </form>

            <p class="text-xs text-gris-400">
                Vous ne voyez que les élèves des classes où vous intervenez.
            </p>
        </div>

        @php($liste = $eleves[$ouverte] ?? collect())

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                    <tr>
                        <th class="w-10 text-center">N°</th>
                        <th>Élève</th>
                        <th>Matricule</th>
                        <th class="text-center">Sexe</th>
                        <th class="text-center">Né(e) le</th>
                        <th>Parent ou tuteur</th>
                        <th class="text-right">Fiche</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($liste as $eleve)
                        @php($parent = $eleve->parents->first())

                        <tr>
                            <td class="text-center tabular-nums text-gris-400">{{ $loop->iteration }}</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :nom="$eleve->first_name.' '.$eleve->last_name" taille="h-8 w-8"/>
                                    <span class="font-medium text-gris-800">
                                        {{ $eleve->last_name }} {{ $eleve->first_name }}
                                    </span>
                                </div>
                            </td>
                            <td class="tabular-nums text-gris-600">{{ $eleve->student_id }}</td>
                            <td class="text-center text-gris-600">{{ $sexe($eleve) }}</td>
                            <td class="text-center tabular-nums text-gris-600">
                                {{ $eleve->date_of_birth ? \Carbon\Carbon::parse($eleve->date_of_birth)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="text-gris-600">
                                @if ($parent)
                                    {{ $parent->last_name }} {{ $parent->first_name }}
                                    @if ($parent->phone)
                                        <span class="block text-[11px] tabular-nums text-gris-400">{{ $parent->phone }}</span>
                                    @endif
                                @else
                                    <span class="text-gris-400">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('students.show', $eleve->id) }}" class="bouton-mini">Ouvrir</a>
                            </td>
                        </tr>
                    @empty
                        <x-vide :colonnes="7"
                                :message="$terme !== ''
                                    ? 'Aucun élève de cette classe ne correspond à cette recherche.'
                                    : 'Aucun élève inscrit dans cette classe.'"/>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @endunless

@endsection
