@extends('layouts.app')

@section('titre', $student->full_name)
@section('sous-titre', 'Matricule '.$student->student_id)

@section('actions-entete')
    <a href="{{ route('students.edit', $student->id) }}" class="bouton-secondaire">Modifier</a>
    <a href="{{ route('enrollments.index') }}" class="bouton-primaire">Nouvelle inscription</a>
@endsection

@section('contenu')

    {{-- Les identifiants du compte qui vient d'etre ouvert : c'est le seul
         endroit ou le mot de passe est encore lisible. --}}
    <x-compte-ouvert/>

@php
    $inscriptions = $student->enrollments->sortByDesc('enrollment_date');
    $inscriptionActuelle = $inscriptions->firstWhere('status', 'active') ?? $inscriptions->first();

    // Compté sur les inscriptions réelles : les colonnes total_enrollments et
    // total_redoublements ne sont pas tenues à jour et affichaient 0 à tort.
    $redoublements = $inscriptions->where('student_status', 'redoublant')->count();
    $resteAPercevoir = (float) $inscriptions->sum('balance_due');

    $libellesStatut = [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'graduated' => 'Diplômé',
        'transferred' => 'Transféré',
    ];
    $teintesStatut = ['active' => 'emerald', 'inactive' => 'slate', 'graduated' => 'sky', 'transferred' => 'amber'];

    $libellesType = ['nouveau' => 'Nouveau', 'redoublant' => 'Redoublant', 'passant' => 'Passant'];
    $teintesType = ['nouveau' => 'sky', 'redoublant' => 'amber', 'passant' => 'emerald'];

    $libellesPaiement = ['completed' => 'Soldé', 'partial' => 'Partiel', 'pending' => 'Impayé', 'overdue' => 'En retard'];
    $teintesPaiement = ['completed' => 'emerald', 'partial' => 'amber', 'pending' => 'rose', 'overdue' => 'rose'];

    $libellesLien = ['father' => 'Père', 'mother' => 'Mère', 'guardian' => 'Tuteur', 'other' => 'Autre'];
    $teintesLien = ['father' => 'sky', 'mother' => 'rose', 'guardian' => 'violet', 'other' => 'slate'];

    $onglets = [
        'profil' => 'Détail du profil',
        'scolarite' => 'Scolarité',
        'responsables' => 'Responsables légaux',
    ];
@endphp

<div x-data="{ onglet: 'profil' }">

    {{-- ------------------------------------------------------------------
         En-tête du dossier
         ------------------------------------------------------------------ --}}
    <div class="carte mb-4 p-5">
        <div class="flex flex-wrap items-start gap-5">
            <x-avatar :personne="$student" taille="h-20 w-20"/>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-gris-900">{{ $student->full_name }}</h2>
                    <x-puce :couleur="$teintesStatut[$student->status] ?? 'slate'">
                        {{ $libellesStatut[$student->status] ?? ucfirst($student->status) }}
                    </x-puce>
                    @if ($redoublements > 0)
                        <x-puce couleur="amber">{{ $redoublements }} redoublement(s)</x-puce>
                    @endif
                    @if ($student->medical_conditions)
                        <x-puce couleur="rose">Suivi médical</x-puce>
                    @endif
                    @if (($student->fitness_status ?? 'apte') === 'inapte')
                        <x-puce couleur="rose">Inapte</x-puce>
                    @endif
                </div>

                <p class="mt-1 text-sm text-gris-500">
                    @if ($inscriptionActuelle?->schoolClass)
                        {{ $inscriptionActuelle->schoolClass->name }}
                        @if ($inscriptionActuelle->schoolClass->level)
                            &middot; {{ $inscriptionActuelle->schoolClass->level->name }}
                        @endif
                        @if ($inscriptionActuelle->academicYear)
                            &middot; {{ $inscriptionActuelle->academicYear->name }}
                        @endif
                    @else
                        Aucune inscription en cours
                    @endif
                </p>

                <div class="mt-3 flex flex-wrap gap-x-8 gap-y-1.5 text-sm [&>div]:whitespace-nowrap">
                    <div>
                        <span class="text-gris-400">Matricule</span>
                        <span class="font-mono font-medium text-ogar-700">{{ $student->student_id }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Âge</span>
                        <span class="font-medium">{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->age.' ans' : '—' }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Sexe</span>
                        <span class="font-medium">{{ $student->gender === 'female' ? 'Féminin' : 'Masculin' }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Entré le</span>
                        <span class="font-medium">{{ $student->enrollment_date?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg bg-ogar-50 px-4 py-3">
                    <div class="text-2xl font-bold text-ogar-700">{{ $inscriptions->count() }}</div>
                    <div class="text-[10px] font-semibold uppercase text-ogar-600">inscriptions</div>
                </div>
                <div class="rounded-lg bg-gris-50 px-4 py-3">
                    <div class="text-2xl font-bold text-gris-700">{{ $student->parents->count() }}</div>
                    <div class="text-[10px] font-semibold uppercase text-gris-500">responsables</div>
                </div>
                <div class="rounded-lg {{ $resteAPercevoir > 0 ? 'bg-corail-50' : 'bg-emerald-50' }} px-4 py-3">
                    <div class="text-2xl font-bold {{ $resteAPercevoir > 0 ? 'text-corail-700' : 'text-emerald-700' }}">
                        {{ number_format($resteAPercevoir, 0, ',', ' ') }}
                    </div>
                    <div class="text-[10px] font-semibold uppercase {{ $resteAPercevoir > 0 ? 'text-corail-600' : 'text-emerald-600' }}">
                        reste à payer
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 border-t border-gris-100 pt-4">
            <a href="{{ route('students.edit', $student->id) }}" class="bouton-secondaire text-xs">Modifier le dossier</a>
            <a href="{{ route('bulletins.index') }}" class="bouton-secondaire text-xs">Bulletins</a>
            <a href="{{ route('payments.index') }}" class="bouton-secondaire text-xs">Paiements</a>

            <x-confirmation :action="route('students.destroy', $student->id)" methode="DELETE"
                            titre="Supprimer cet élève ?"
                            :message="'Le dossier de '.$student->full_name.' et ses '.$inscriptions->count().' inscription(s) seront définitivement supprimés.'"
                            confirmer="Supprimer"
                            bouton="bouton-secondaire ml-auto text-xs text-corail-600 hover:bg-corail-50">
                Supprimer le dossier
            </x-confirmation>
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Onglets
         ------------------------------------------------------------------ --}}
    <div class="mb-4 flex flex-wrap gap-1 border-b border-gris-200">
        @foreach ($onglets as $cle => $libelle)
            <button type="button" @click="onglet = '{{ $cle }}'"
                    :class="onglet === '{{ $cle }}' ? 'border-ogar-700 text-ogar-700' : 'border-transparent text-gris-500 hover:text-gris-700'"
                    class="cursor-pointer border-b-2 px-4 py-2 text-sm font-semibold transition">{{ $libelle }}</button>
        @endforeach
    </div>

    {{-- Profil ------------------------------------------------------------ --}}
    <div x-show="onglet === 'profil'" class="grid gap-4 lg:grid-cols-2">
        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">État civil</h3>
                <a href="{{ route('students.edit', $student->id) }}" class="text-xs font-semibold text-ogar-600 hover:underline">Modifier</a>
            </div>
            <dl class="grid grid-cols-2 gap-y-2.5 p-5 text-sm">
                @foreach ([
                    'Prénom' => $student->first_name,
                    'Nom' => $student->last_name,
                    'Date de naissance' => $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : null,
                    'Lieu de naissance' => $student->place_of_birth,
                    'Sexe' => $student->gender === 'female' ? 'Féminin' : 'Masculin',
                    'Adresse' => $student->address,
                    'Téléphone' => $student->phone,
                    'Courriel' => $student->email,
                    'Contact d’urgence' => $student->emergency_contact,
                ] as $libelle => $valeur)
                    <dt class="text-gris-400">{{ $libelle }}</dt>
                    <dd class="font-medium text-gris-800">{{ $valeur ?: '—' }}</dd>
                @endforeach
            </dl>
        </div>

        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Dossier et santé</h3>
            </div>
            <dl class="grid grid-cols-2 gap-y-2.5 px-5 pt-5 text-sm">
                @foreach ([
                    'Matricule' => $student->student_id,
                    'Statut administratif' => $libellesStatut[$student->status] ?? $student->status,
                    'Statut de scolarité' => $student->current_status ? ucfirst($student->current_status) : null,
                    'Date d’entrée' => $student->enrollment_date?->format('d/m/Y'),
                    'Inscriptions' => $inscriptions->count(),
                    'Redoublements' => $redoublements,
                ] as $libelle => $valeur)
                    <dt class="text-gris-400">{{ $libelle }}</dt>
                    <dd class="font-medium text-gris-800">{{ ($valeur === null || $valeur === '') ? '—' : $valeur }}</dd>
                @endforeach
            </dl>

            <div class="mt-4 border-t border-gris-100 px-5 pt-3">
                <h4 class="etiquette">Conditions médicales</h4>
                <p class="text-sm leading-relaxed {{ $student->medical_conditions ? 'text-corail-800' : 'text-gris-400' }}">
                    {{ $student->medical_conditions ?: 'Aucune signalée' }}
                </p>
            </div>

            <div class="mt-3 border-t border-gris-100 px-5 pt-3">
                <h4 class="etiquette">Aptitude</h4>
                @if (($student->fitness_status ?? 'apte') === 'inapte')
                    <div class="flex items-start gap-2">
                        <x-puce couleur="rose">Inapte</x-puce>
                        <p class="text-sm leading-relaxed text-corail-800">
                            {{ $student->unfitness_reason ?: 'Motif non précisé' }}
                        </p>
                    </div>
                @else
                    <div class="flex items-center gap-2">
                        <x-puce couleur="emerald">Apte</x-puce>
                        <span class="text-sm text-gris-400">Aucune restriction</span>
                    </div>
                @endif
            </div>

            @if ($student->history_comments)
                <div class="mt-3 border-t border-gris-100 px-5 pt-3">
                    <h4 class="etiquette">Commentaires</h4>
                    <p class="text-sm leading-relaxed text-gris-600">{{ $student->history_comments }}</p>
                </div>
            @endif

            <div class="h-5"></div>
        </div>
    </div>

    {{-- Scolarité --------------------------------------------------------- --}}
    <div x-show="onglet === 'scolarite'" x-cloak class="space-y-4">
        @if ($inscriptions->isNotEmpty())
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-statistique libelle="Inscriptions" :valeur="$inscriptions->count()" couleur="ogar"/>
                <x-statistique libelle="Frais appelés"
                               :valeur="number_format($inscriptions->sum('total_fees'), 0, ',', ' ').' F'"
                               couleur="slate"/>
                <x-statistique libelle="Encaissé"
                               :valeur="number_format($inscriptions->sum('amount_paid'), 0, ',', ' ').' F'"
                               couleur="emerald"/>
                <x-statistique libelle="Reste à percevoir"
                               :valeur="number_format($resteAPercevoir, 0, ',', ' ').' F'"
                               :couleur="$resteAPercevoir > 0 ? 'rose' : 'emerald'"/>
            </div>
        @endif

        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Historique des inscriptions</h3>
                <a href="{{ route('enrollments.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                    Module Inscriptions
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Année scolaire</th>
                            <th>Classe</th>
                            <th>Niveau</th>
                            <th>Type</th>
                            <th>Paiement</th>
                            <th>Date</th>
                            <th>État</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inscriptions as $inscription)
                            <tr>
                                <td class="whitespace-nowrap font-medium text-gris-800">
                                    {{ $inscription->academicYear->name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap">{{ $inscription->schoolClass->name ?? '—' }}</td>
                                <td class="whitespace-nowrap text-gris-500">{{ $inscription->schoolClass->level->name ?? '—' }}</td>
                                <td>
                                    <x-puce :couleur="$teintesType[$inscription->student_status] ?? 'slate'" class="whitespace-nowrap">
                                        {{ $libellesType[$inscription->student_status] ?? '—' }}
                                    </x-puce>
                                </td>
                                <td>
                                    <x-puce :couleur="$teintesPaiement[$inscription->payment_status] ?? 'slate'" class="whitespace-nowrap">
                                        {{ $libellesPaiement[$inscription->payment_status] ?? ucfirst($inscription->payment_status ?? '—') }}
                                    </x-puce>
                                    @if ($inscription->total_fees > 0)
                                        <div class="mt-1 whitespace-nowrap text-[11px] text-gris-400">
                                            {{ number_format($inscription->amount_paid, 0, ',', ' ') }} /
                                            {{ number_format($inscription->total_fees, 0, ',', ' ') }} F
                                        </div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-gris-500">
                                    {{ $inscription->enrollment_date?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td>
                                    @if ($inscription->status === 'active')
                                        <x-puce couleur="emerald">En cours</x-puce>
                                    @else
                                        <x-puce>{{ ucfirst($inscription->status) }}</x-puce>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="7" message="Cet élève n’a encore aucune inscription."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Responsables ------------------------------------------------------ --}}
    <div x-show="onglet === 'responsables'" x-cloak>
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Responsables légaux</h3>
                <a href="{{ route('parents.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                    Module Parents
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Responsable</th>
                            <th>Lien</th>
                            <th>Téléphone</th>
                            <th>Adresse e-mail</th>
                            <th>Profession</th>
                            <th>Rôle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($student->parents as $parent)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar :nom="$parent->first_name.' '.$parent->last_name"/>
                                        <a href="{{ route('parents.show', $parent->id) }}"
                                           class="font-semibold text-gris-900 hover:text-ogar-700">
                                            {{ $parent->first_name }} {{ $parent->last_name }}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <x-puce :couleur="$teintesLien[$parent->relationship] ?? 'slate'" class="whitespace-nowrap">
                                        {{ $libellesLien[$parent->relationship] ?? ucfirst($parent->relationship ?? '—') }}
                                    </x-puce>
                                </td>
                                <td class="whitespace-nowrap">
                                    @if ($parent->phone)
                                        <a href="tel:{{ $parent->phone }}" class="text-gris-600 hover:text-ogar-700">{{ $parent->phone }}</a>
                                    @else
                                        <span class="text-gris-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($parent->email)
                                        <a href="mailto:{{ $parent->email }}" class="text-gris-600 hover:text-ogar-700">{{ $parent->email }}</a>
                                    @else
                                        <span class="text-gris-400">—</span>
                                    @endif
                                </td>
                                <td class="text-gris-600">{{ $parent->profession ?: '—' }}</td>
                                <td>
                                    @if ($parent->pivot?->is_primary_contact)
                                        <x-puce couleur="ogar" class="whitespace-nowrap">Contact principal</x-puce>
                                    @else
                                        <span class="text-xs text-gris-400">Secondaire</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="6" message="Aucun responsable légal rattaché à cet élève."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
