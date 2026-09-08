@extends('layouts.app')

@section('titre', 'Ma fiche élève')
@section('sous-titre', $eleve->student_id.' · '.(optional($inscription?->schoolClass)->name ?? 'sans classe').' — '.($annee->name ?? ''))

@section('actions-entete')
    <button type="button"
            class="bouton-primaire"
            data-export-pdf="fiche-eleve"
            data-page-unique
            data-nom-fichier="Fiche_eleve_{{ \Illuminate\Support\Str::slug($eleve->last_name.' '.$eleve->first_name) }}.pdf">
        Télécharger ma fiche
    </button>
    <a href="{{ route('mon-espace') }}" class="bouton-secondaire">Mon tableau de bord</a>
@endsection

@section('contenu')

@php
    $etablissement = \App\Models\SchoolSettings::getSettings();
    $montant = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';

    $sexe = match (mb_strtolower((string) $eleve->gender)) {
        'm', 'male', 'masculin', 'garcon', 'garçon' => 'Masculin',
        'f', 'female', 'feminin', 'féminin', 'fille' => 'Féminin',
        default => '—',
    };

    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '—';

    // Le lien de parente est stocke en anglais : un document officiel
    // francais ne peut pas afficher « guardian ».
    $lien = fn ($v) => match (mb_strtolower((string) $v)) {
        'father' => 'Père',
        'mother' => 'Mère',
        'guardian', 'tutor' => 'Tuteur',
        'brother' => 'Frère',
        'sister' => 'Sœur',
        'uncle' => 'Oncle',
        'aunt' => 'Tante',
        'grandfather' => 'Grand-père',
        'grandmother' => 'Grand-mère',
        'other' => 'Autre',
        '' => '—',
        default => ucfirst((string) $v),
    };
@endphp

    {{-- ------------------------------------------------------------------
         Le document. Ce bloc est celui que html2canvas photographie :
         le PDF est exactement ce qui s'affiche ici.
         ------------------------------------------------------------------ --}}
    <div id="fiche-eleve" class="mx-auto max-w-4xl bg-white p-8 text-gris-900 ring-1 ring-gris-200">

        {{-- En-tête officiel : logo de l'établissement à gauche, sceau de la
             République à droite, comme sur le bulletin. --}}
        <div class="flex items-start justify-between gap-4 border-b-2 border-gris-800 pb-3">
            <div class="flex items-start gap-3">
                @if ($etablissement->logo_url ?? null)
                    <img src="{{ $etablissement->logo_url }}" alt="Logo de l’établissement"
                         class="h-14 w-14 shrink-0 object-contain">
                @else
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Logo<br>établissement
                    </span>
                @endif
                <div class="leading-tight">
                    <p class="text-[10px] text-gris-600">Ministère de l’Éducation Nationale</p>
                    <p class="text-sm font-bold uppercase">{{ $etablissement->school_name ?? 'Établissement scolaire' }}</p>
                    <p class="text-[9px] text-gris-500">
                        @if ($etablissement->school_bp ?? null) {{ $etablissement->school_bp }} @endif
                        @if ($etablissement->school_phone ?? null) &middot; Tél : {{ $etablissement->school_phone }} @endif
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="text-right leading-tight">
                    <p class="text-[10px] uppercase tracking-wide text-gris-500">Année scolaire</p>
                    <p class="text-sm font-semibold">{{ $annee->name ?? '—' }}</p>
                </div>

                @if ($etablissement->seal_url ?? null)
                    <img src="{{ $etablissement->seal_url }}" alt="Sceau de la République"
                         class="h-14 w-16 shrink-0 object-contain">
                @else
                    <span class="flex h-14 w-16 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Sceau de la<br>République
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-5 text-center">
            <h1 class="text-lg font-bold uppercase tracking-wide">Fiche de l’élève</h1>
            <p class="mt-0.5 font-mono text-xs text-gris-500">Matricule {{ $eleve->student_id }}</p>
        </div>

        {{-- Identité --}}
        <div class="mt-5 flex items-start gap-5">
            <div class="shrink-0">
                @if ($eleve->photo)
                    <img src="{{ asset('storage/'.$eleve->photo) }}" alt="Photographie de l’élève"
                         class="h-28 w-24 border border-gris-400 object-cover">
                @else
                    <span class="flex h-28 w-24 items-center justify-center border border-dashed border-gris-400 text-center text-[8px] leading-tight text-gris-400">
                        Photographie
                    </span>
                @endif
            </div>

            <div class="flex-1">
                <h2 class="mb-2 border-b border-gris-300 pb-1 text-[11px] font-bold uppercase tracking-wide text-gris-600">
                    État civil
                </h2>

                <dl class="grid grid-cols-2 gap-x-6 gap-y-1.5 text-[11px]">
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Nom</dt>
                        <dd class="font-semibold uppercase">{{ $eleve->last_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Prénoms</dt>
                        <dd class="font-semibold">{{ $eleve->first_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Né(e) le</dt>
                        <dd class="font-medium tabular-nums">{{ $date($eleve->date_of_birth) }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">À</dt>
                        <dd class="font-medium">{{ $eleve->place_of_birth ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Sexe</dt>
                        <dd class="font-medium">{{ $sexe }}</dd>
                    </div>
                    <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Téléphone</dt>
                        <dd class="font-medium tabular-nums">{{ $eleve->phone ?: '—' }}</dd>
                    </div>
                    <div class="col-span-2 flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                        <dt class="text-gris-500">Adresse</dt>
                        <dd class="font-medium">{{ $eleve->address ?: '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Scolarité --}}
        <h2 class="mb-2 mt-6 border-b border-gris-300 pb-1 text-[11px] font-bold uppercase tracking-wide text-gris-600">
            Scolarité de l’année
        </h2>

        <div class="grid grid-cols-4 gap-px border border-gris-400 bg-gris-400 text-center text-[10px]">
            <div class="bg-white px-2 py-2">
                <p class="uppercase tracking-wide text-gris-500">Classe</p>
                <p class="mt-0.5 text-sm font-bold">{{ optional($inscription?->schoolClass)->name ?? '—' }}</p>
            </div>
            <div class="bg-white px-2 py-2">
                <p class="uppercase tracking-wide text-gris-500">Niveau</p>
                <p class="mt-0.5 text-sm font-bold">{{ optional($inscription?->schoolClass?->level)->name ?? '—' }}</p>
            </div>
            <div class="bg-white px-2 py-2">
                <p class="uppercase tracking-wide text-gris-500">Moyenne générale</p>
                <p class="mt-0.5 text-sm font-bold">
                    {{ $moyenneGenerale !== null ? number_format($moyenneGenerale, 2, ',', ' ').' / 20' : '—' }}
                </p>
            </div>
            <div class="bg-white px-2 py-2">
                <p class="uppercase tracking-wide text-gris-500">Présence</p>
                <p class="mt-0.5 text-sm font-bold">
                    {{ $assiduite['taux'] !== null ? $assiduite['taux'].' %' : '—' }}
                </p>
            </div>
        </div>

        <dl class="mt-3 grid grid-cols-3 gap-x-6 gap-y-1.5 text-[11px]">
            <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                <dt class="text-gris-500">Date d’inscription</dt>
                <dd class="font-medium tabular-nums">{{ $date($eleve->enrollment_date) }}</dd>
            </div>
            <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                <dt class="text-gris-500">Absences</dt>
                <dd class="font-medium tabular-nums">{{ $assiduite['absent'] }}</dd>
            </div>
            <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                <dt class="text-gris-500">Retards</dt>
                <dd class="font-medium tabular-nums">{{ $assiduite['late'] }}</dd>
            </div>
            <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                <dt class="text-gris-500">Frais dus</dt>
                <dd class="font-medium tabular-nums">{{ $montant($scolarite['du']) }}</dd>
            </div>
            <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                <dt class="text-gris-500">Réglé</dt>
                <dd class="font-medium tabular-nums">{{ $montant($scolarite['paye']) }}</dd>
            </div>
            <div class="flex justify-between gap-2 border-b border-dotted border-gris-200 pb-1">
                <dt class="text-gris-500">Reste à payer</dt>
                <dd class="font-medium tabular-nums">{{ $montant($scolarite['reste']) }}</dd>
            </div>
        </dl>

        {{-- Parents et tuteurs --}}
        <h2 class="mb-2 mt-6 border-b border-gris-300 pb-1 text-[11px] font-bold uppercase tracking-wide text-gris-600">
            Parents et tuteurs
        </h2>

        <table class="w-full border-collapse text-[10px]">
            <thead>
                <tr>
                    <th class="border border-gris-400 bg-gris-200 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">Nom et prénoms</th>
                    <th class="w-28 border border-gris-400 bg-gris-200 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">Lien</th>
                    <th class="w-32 border border-gris-400 bg-gris-200 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">Téléphone</th>
                    <th class="border border-gris-400 bg-gris-200 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700">Profession</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($eleve->parents as $parent)
                    <tr>
                        <td class="border border-gris-400 px-2 py-1 font-medium">
                            {{ $parent->last_name }} {{ $parent->first_name }}
                        </td>
                        <td class="border border-gris-400 px-2 py-1">
                            {{ $lien($parent->pivot->relationship_type) }}
                        </td>
                        <td class="border border-gris-400 px-2 py-1 tabular-nums">{{ $parent->phone ?: '—' }}</td>
                        <td class="border border-gris-400 px-2 py-1">{{ $parent->profession ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="border border-gris-400 px-2 py-3 text-center text-gris-400">
                            Aucun parent n’est rattaché à ce dossier.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pied : la fiche se date et se signe. --}}
        <div class="mt-8 flex items-end justify-between text-[9px] text-gris-500">
            <p>
                Fiche éditée le {{ now()->locale('fr')->isoFormat('D MMMM YYYY à HH:mm') }}<br>
                {{ $etablissement->school_name ?? 'Établissement scolaire' }}
            </p>

            <div class="text-center">
                <p class="text-gris-600">Le chef d’établissement</p>
                <p class="mt-10 border-t border-gris-400 px-10 pt-1">Signature et cachet</p>
            </div>
        </div>
    </div>

@endsection
