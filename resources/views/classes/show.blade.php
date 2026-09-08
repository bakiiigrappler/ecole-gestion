@extends('layouts.app')

@section('titre', $class->name)
@section('sous-titre', $class->getSafeLevelName().' — '.($class->series ? 'série '.$class->series : 'toutes séries'))

@section('actions-entete')
    <a href="{{ route('classes.edit', $class->id) }}" class="bouton-secondaire">Modifier</a>
    <a href="{{ route('enrollments.index') }}" class="bouton-primaire">Inscrire un élève</a>
@endsection

@section('contenu')

@php
    $libellesCycle = [
        'preprimaire' => 'Préprimaire',
        'primaire' => 'Primaire',
        'college' => 'Collège',
        'lycee' => 'Lycée',
    ];
    $teintesCycle = ['preprimaire' => 'amber', 'primaire' => 'emerald', 'college' => 'sky', 'lycee' => 'violet'];

    $cycle = $class->getSafeCycle();
    $capacite = (int) $class->capacity;
    $effectif = $eleves->count();
    $taux = $capacite > 0 ? (int) round($effectif / $capacite * 100) : null;

    $equipe = $class->allTeachers;
    $principal = $equipe->firstWhere('pivot.role', 'principal');
    $intervenants = $equipe->where('pivot.role', '!=', 'principal');

    $creneaux = $class->schedules->sortBy(['day_of_week', 'start_time']);
    $jours = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
        'monday' => 'Lundi', 'tuesday' => 'Mardi', 'wednesday' => 'Mercredi',
        'thursday' => 'Jeudi', 'friday' => 'Vendredi', 'saturday' => 'Samedi',
    ];

    $onglets = [
        'detail' => 'Détail',
        'eleves' => 'Élèves ('.$effectif.')',
        'equipe' => 'Équipe pédagogique ('.$equipe->count().')',
        'presences' => 'Présences',
        'notes' => 'Notes',
        'emploi' => 'Emploi du temps',
    ];

    // Classes ecrites en entier : Tailwind ne compile pas une teinte interpolee.
    $tuilesPresence = [
        ['Présences', $presences['presents'], 'text-emerald-700', 'text-emerald-600'],
        ['Absences', $presences['absents'], 'text-corail-700', 'text-corail-600'],
        ['Retards', $presences['retards'], 'text-soleil-700', 'text-soleil-600'],
        ['Justifiées', $presences['excuses'], 'text-gris-700', 'text-gris-500'],
    ];

    $ongletInitial = request('onglet', 'detail');
    $ongletInitial = array_key_exists($ongletInitial, $onglets) ? $ongletInitial : 'detail';
@endphp

<div x-data="{ onglet: '{{ $ongletInitial }}' }">

    {{-- ------------------------------------------------------------------
         En-tête de la classe
         ------------------------------------------------------------------ --}}
    <div class="carte mb-4 p-5">
        <div class="flex flex-wrap items-start gap-5">
            <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-ogar-50 ring-1 ring-ogar-100">
                <svg class="h-9 w-9 text-ogar-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z"/>
                </svg>
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-gris-900">{{ $class->name }}</h2>
                    <x-puce :couleur="$teintesCycle[$cycle] ?? 'slate'">{{ $libellesCycle[$cycle] ?? ucfirst($cycle) }}</x-puce>
                    @if (! $class->is_active)
                        <x-puce couleur="rose">Fermée</x-puce>
                    @endif
                    @if ($equipe->isEmpty())
                        <x-puce couleur="rose">Sans enseignant</x-puce>
                    @endif
                </div>

                <p class="mt-1 text-sm text-gris-500">
                    {{ $class->getSafeLevelName() }}
                    @if ($class->series) &middot; série {{ $class->series }} @endif
                    @if ($principal) &middot; {{ $principal->full_name }} (principal) @endif
                </p>

                <div class="mt-3 flex flex-wrap gap-x-8 gap-y-1.5 text-sm [&>div]:whitespace-nowrap">
                    <div>
                        <span class="text-gris-400">Capacité</span>
                        <span class="font-medium">{{ $capacite ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Places libres</span>
                        <span class="font-medium">{{ $capacite > 0 ? max($capacite - $effectif, 0) : '—' }}</span>
                    </div>
                    <div>
                        <span class="text-gris-400">Créneaux</span>
                        <span class="font-medium">{{ $creneaux->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-lg bg-ogar-50 px-4 py-3">
                    <div class="text-2xl font-bold text-ogar-700">{{ $effectif }}</div>
                    <div class="text-[10px] font-semibold uppercase text-ogar-600">élèves</div>
                </div>
                <div class="rounded-lg {{ $equipe->isEmpty() ? 'bg-corail-50' : 'bg-gris-50' }} px-4 py-3">
                    <div class="text-2xl font-bold {{ $equipe->isEmpty() ? 'text-corail-700' : 'text-gris-700' }}">{{ $equipe->count() }}</div>
                    <div class="text-[10px] font-semibold uppercase {{ $equipe->isEmpty() ? 'text-corail-600' : 'text-gris-500' }}">enseignants</div>
                </div>
                <div class="rounded-lg bg-soleil-50 px-4 py-3">
                    <div class="text-2xl font-bold text-soleil-700">{{ $taux !== null ? $taux.'%' : '—' }}</div>
                    <div class="text-[10px] font-semibold uppercase text-soleil-600">occupation</div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 border-t border-gris-100 pt-4">
            <a href="{{ route('classes.edit', $class->id) }}" class="bouton-secondaire text-xs">Modifier la classe</a>
            <button type="button" @click="onglet = 'presences'" class="bouton-secondaire text-xs">
                Présences
                @if ($presences['taux'] !== null)
                    <span class="ml-1 font-semibold {{ $presences['taux'] >= 90 ? 'text-emerald-600' : ($presences['taux'] >= 75 ? 'text-soleil-600' : 'text-corail-600') }}">
                        {{ $presences['taux'] }}%
                    </span>
                @endif
            </button>
            <button type="button" @click="onglet = 'notes'" class="bouton-secondaire text-xs">
                Notes
                @if ($notes['moyenne'] !== null)
                    <span class="ml-1 font-semibold text-gris-700">{{ number_format($notes['moyenne'], 2, ',', ' ') }}/20</span>
                @endif
            </button>

            <x-confirmation :action="route('classes.destroy', $class->id)" methode="DELETE"
                            titre="Supprimer cette classe ?"
                            :message="'La classe '.$class->name.' compte '.$effectif.' élève(s) inscrit(s). La suppression est définitive.'"
                            confirmer="Supprimer"
                            bouton="bouton-secondaire ml-auto text-xs text-corail-600 hover:bg-corail-50">
                Supprimer la classe
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

    {{-- Détail ------------------------------------------------------------ --}}
    <div x-show="onglet === 'detail'" class="grid gap-4 lg:grid-cols-2">
        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Informations</h3>
                <a href="{{ route('classes.edit', $class->id) }}" class="text-xs font-semibold text-ogar-600 hover:underline">Modifier</a>
            </div>
            <dl class="grid grid-cols-2 gap-y-2.5 p-5 text-sm">
                @foreach ([
                    'Nom' => $class->name,
                    'Niveau' => $class->getSafeLevelName(),
                    'Cycle' => $libellesCycle[$cycle] ?? ucfirst($cycle),
                    'Série' => $class->series,
                    'Capacité' => $capacite ?: null,
                    'Statut' => $class->is_active ? 'Ouverte' : 'Fermée',
                    'Créée le' => $class->created_at?->format('d/m/Y'),
                ] as $libelle => $valeur)
                    <dt class="text-gris-400">{{ $libelle }}</dt>
                    <dd class="font-medium text-gris-800">{{ $valeur ?: '—' }}</dd>
                @endforeach
            </dl>

            @if ($class->description)
                <div class="border-t border-gris-100 px-5 pb-5 pt-3">
                    <h4 class="etiquette">Description</h4>
                    <p class="text-sm leading-relaxed text-gris-600">{{ $class->description }}</p>
                </div>
            @endif
        </div>

        <div class="carte">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Occupation</h3>
            </div>

            <div class="space-y-4 p-5">
                <div>
                    <div class="mb-1.5 flex items-baseline justify-between gap-3">
                        <span class="text-sm text-gris-500">Effectif</span>
                        <span class="text-sm">
                            <span class="text-lg font-bold text-gris-900">{{ $effectif }}</span>
                            <span class="text-gris-400">/ {{ $capacite ?: '—' }}</span>
                        </span>
                    </div>

                    <div class="h-2 overflow-hidden rounded-full bg-gris-100"
                         role="progressbar" aria-valuenow="{{ $taux ?? 0 }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full {{ $taux === null ? 'bg-gris-300' : ($taux >= 100 ? 'bg-corail-600' : ($taux >= 85 ? 'bg-soleil-500' : 'bg-emerald-600')) }}"
                             style="width: {{ min($taux ?? 0, 100) }}%"></div>
                    </div>

                    <p class="mt-1 text-xs text-gris-400">
                        @if ($taux === null)
                            Capacité non renseignée : l’occupation ne peut pas être calculée.
                        @elseif ($taux >= 100)
                            La classe est pleine.
                        @else
                            {{ $capacite - $effectif }} place(s) encore disponible(s).
                        @endif
                    </p>
                </div>

                <dl class="space-y-2 border-t border-gris-100 pt-4 text-sm">
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-gris-500">Professeur principal</dt>
                        <dd class="text-right font-medium text-gris-800">{{ $principal?->full_name ?? '—' }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-gris-500">Intervenants</dt>
                        <dd class="font-medium text-gris-800">{{ $intervenants->count() }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-gris-500">Créneaux planifiés</dt>
                        <dd class="font-medium text-gris-800">{{ $creneaux->count() }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    {{-- Élèves ------------------------------------------------------------ --}}
    <div x-show="onglet === 'eleves'" x-cloak>
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Élèves inscrits</h3>
                <a href="{{ route('students.index') }}?class={{ $class->id }}"
                   class="text-xs font-semibold text-ogar-600 hover:underline">Voir dans le module Élèves</a>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Matricule</th>
                            <th>Âge</th>
                            <th>Année</th>
                            <th>Statut</th>
                            <th class="w-20"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($eleves as $eleve)
                            @php($inscription = $eleve->enrollments->first())
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar :personne="$eleve"/>
                                        <a href="{{ route('students.show', $eleve->id) }}"
                                           class="truncate font-semibold text-gris-900 hover:text-ogar-700">
                                            {{ $eleve->last_name }} {{ $eleve->first_name }}
                                        </a>
                                    </div>
                                </td>
                                <td><span class="font-mono text-xs font-semibold text-ogar-700">{{ $eleve->student_id }}</span></td>
                                <td class="whitespace-nowrap text-gris-500">
                                    {{ $eleve->date_of_birth ? \Carbon\Carbon::parse($eleve->date_of_birth)->age.' ans' : '—' }}
                                </td>
                                <td class="whitespace-nowrap text-gris-500">{{ $inscription?->academicYear->name ?? '—' }}</td>
                                <td>
                                    @if ($eleve->status === 'active')
                                        <x-puce couleur="emerald">Actif</x-puce>
                                    @else
                                        <x-puce>{{ ucfirst($eleve->status) }}</x-puce>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex justify-end">
                                        <a href="{{ route('students.show', $eleve->id) }}" class="bouton-mini">Fiche</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="6" message="Aucun élève inscrit dans cette classe pour l’année en cours."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Équipe pédagogique ------------------------------------------------- --}}
    <div x-show="onglet === 'equipe'" x-cloak
         x-data="{
             equipe: {{ Js::from($equipe->map(fn ($e) => (string) $e->id)->values()) }},
             principal: '{{ $principal?->id }}',
             initial: '',
             recherche: '',
             ouvert: false,
             surligne: 0,

             matieres: {{ Js::from($matieresDuCycle->map(fn ($m) => ['id' => (string) $m->id, 'nom' => $m->name])->values()) }},
             candidats: {{ Js::from($enseignantsDisponibles->map(fn ($e) => [
                 'id' => (string) $e->id,
                 'nom' => $e->full_name,
                 'matricule' => $e->employee_id,
                 'specialite' => $e->teacher_type === 'general' ? 'Polyvalent' : ($e->specialization ?: 'Spécialisé'),
                 'initiales' => mb_strtoupper(mb_substr($e->first_name, 0, 1).mb_substr($e->last_name, 0, 1)),
                 'charge' => (int) ($chargeParEnseignant[$e->id] ?? 0),
                 'matieres' => $matieresDuCycle->filter(fn ($m) => $e->couvreLaMatiere($m))
                     ->map(fn ($m) => (string) $m->id)->values(),
             ])->values()) }},

             init() { this.initial = this.empreinte(); },

             empreinte() {
                 return [...this.equipe].sort().join(',') + '|' + this.principal;
             },
             get modifie() { return this.empreinte() !== this.initial; },

             get membres() {
                 return this.equipe.map((id) => this.candidats.find((c) => c.id === id)).filter(Boolean);
             },

             get couvertes() {
                 return new Set(this.membres.flatMap((m) => m.matieres));
             },
             get manquantes() {
                 return this.matieres.filter((m) => ! this.couvertes.has(m.id));
             },
             comble(candidat) {
                 const trous = new Set(this.manquantes.map((m) => m.id));
                 return candidat.matieres.filter((id) => trous.has(id));
             },
             nomMatiere(id) {
                 return (this.matieres.find((m) => m.id === id) || {}).nom || '';
             },

             get resultats() {
                 const motif = this.recherche.trim().toLowerCase();
                 let libres = this.candidats.filter((c) => ! this.equipe.includes(c.id));

                 if (motif) {
                     libres = libres.filter((c) =>
                         [c.nom, c.matricule, c.specialite, ...c.matieres.map((id) => this.nomMatiere(id))]
                             .some((v) => (v || '').toLowerCase().includes(motif))
                     );
                 }

                 return libres
                     .map((c) => ({ ...c, lacunes: this.comble(c) }))
                     .sort((a, b) => (b.lacunes.length - a.lacunes.length) || a.nom.localeCompare(b.nom))
                     .slice(0, 30);
             },

             ajouter(candidat) {
                 if (! candidat || this.equipe.includes(candidat.id)) return;
                 this.equipe.push(candidat.id);
                 if (! this.principal) this.principal = candidat.id;
                 this.recherche = '';
                 this.surligne = 0;
                 this.ouvert = false;
             },
             retirer(id) {
                 this.equipe = this.equipe.filter((x) => x !== id);
                 if (this.principal === id) this.principal = this.equipe[0] || '';
             },

             deplacer(pas) {
                 this.ouvert = true;
                 const n = this.resultats.length;
                 if (! n) return;
                 this.surligne = (this.surligne + pas + n) % n;
             },
             valider() {
                 if (this.ouvert) this.ajouter(this.resultats[this.surligne]);
             },
             chercherMatiere(nom) {
                 this.recherche = nom;
                 this.ouvert = true;
                 this.surligne = 0;
                 this.$refs.champRecherche.focus();
             },
         }"
         class="space-y-4">

        {{-- Les alertes suivent l'état courant du formulaire, pas celui enregistré :
             on voit disparaître le problème au moment où on le corrige. --}}
        <div x-show="! equipe.length"
             class="flex items-start gap-3 rounded-xl border border-corail-200 bg-corail-50 px-4 py-3 text-sm text-corail-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <span>
                <strong>Cette classe n’a aucun enseignant.</strong>
                Les notes, les présences et les bulletins ne pourront pas être saisis tant qu’un
                professeur principal n’est pas désigné.
            </span>
        </div>

        <div x-show="equipe.length && ! principal" x-cloak
             class="flex items-start gap-3 rounded-xl border border-soleil-200 bg-soleil-50 px-4 py-3 text-sm text-soleil-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span>Aucun professeur principal n’est désigné dans cette équipe.</span>
        </div>

        {{-- Composition de l'équipe : un seul formulaire, un seul enregistrement --}}
        <form method="POST" action="{{ route('classes.updateTeachers', $class->id) }}" class="carte">
            @csrf

            <div class="carte-entete">
                <div>
                    <h3 class="text-sm font-semibold text-gris-900">Composition de l’équipe</h3>
                    <p class="mt-0.5 text-xs text-gris-400">
                        <span x-text="equipe.length"></span> enseignant(s) &middot;
                        un seul professeur principal
                        <template x-if="matieres.length">
                            <span>
                                &middot; <span x-text="matieres.length - manquantes.length"></span>/<span x-text="matieres.length"></span> matières couvertes
                            </span>
                        </template>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span x-show="modifie" x-cloak class="text-xs font-medium text-soleil-700">
                        Modifications non enregistrées
                    </span>
                    <button type="submit" class="bouton-primaire" :disabled="! modifie"
                            :class="modifie ? '' : 'cursor-not-allowed opacity-50'">
                        Enregistrer l’équipe
                    </button>
                </div>
            </div>

            @error('principal') <p class="px-5 pt-4 text-xs text-corail-700">{{ $message }}</p> @enderror

            <div class="space-y-4 p-5">
                {{-- Ajouter un enseignant --}}
                <div class="relative" @click.outside="ouvert = false">
                    <label class="etiquette" for="ajout-enseignant">Ajouter un enseignant</label>
                    <input id="ajout-enseignant" type="text" class="champ" autocomplete="off"
                           x-ref="champRecherche"
                           placeholder="Nom, matricule ou matière…"
                           x-model="recherche"
                           @focus="ouvert = true" @input="ouvert = true; surligne = 0"
                           @keydown.escape.prevent="ouvert = false"
                           @keydown.arrow-down.prevent="deplacer(1)"
                           @keydown.arrow-up.prevent="deplacer(-1)"
                           @keydown.enter.prevent="valider()"
                           role="combobox" aria-autocomplete="list" :aria-expanded="ouvert">

                    <p class="mt-1 text-[11px] text-gris-400">
                        Seuls les enseignants du cycle {{ $libellesCycle[$cycle] ?? $cycle }} sont proposés.
                        Ceux qui comblent une matière découverte apparaissent en premier.
                    </p>

                    <ul x-show="ouvert" x-cloak role="listbox"
                        class="absolute z-30 mt-1 max-h-80 w-full overflow-y-auto rounded-lg border border-gris-200 bg-white shadow-lg">
                        <template x-for="(candidat, i) in resultats" :key="candidat.id">
                            <li role="option" :aria-selected="i === surligne"
                                @click="ajouter(candidat)" @mouseenter="surligne = i"
                                class="flex cursor-pointer items-center gap-3 px-3 py-2 transition-colors"
                                :class="i === surligne ? 'bg-ogar-50' : ''">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gris-100 text-[10px] font-bold text-gris-600"
                                      x-text="candidat.initiales"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-gris-800" x-text="candidat.nom"></span>
                                    <span class="block truncate text-[11px] text-gris-400">
                                        <span class="font-mono" x-text="candidat.matricule"></span>
                                        &middot; <span x-text="candidat.specialite"></span>
                                        &middot; <span x-text="candidat.charge"></span> classe(s)
                                    </span>
                                </span>
                                <template x-if="candidat.lacunes.length">
                                    <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                                        + <span x-text="candidat.lacunes.map((id) => nomMatiere(id)).join(', ')"></span>
                                    </span>
                                </template>
                            </li>
                        </template>
                        <li x-show="! resultats.length" class="px-3 py-6 text-center text-sm text-gris-400">
                            Aucun enseignant disponible ne correspond.
                        </li>
                    </ul>
                </div>

                {{-- Membres de l'équipe --}}
                <div class="space-y-2 border-t border-gris-100 pt-4">
                    <p x-show="! membres.length" class="py-4 text-center text-sm text-gris-400">
                        Aucun enseignant dans l’équipe. Ajoutez-en un ci-dessus.
                    </p>

                    <template x-for="membre in membres" :key="membre.id">
                        <div class="rounded-xl border p-3"
                             :class="principal === membre.id ? 'border-ogar-300 bg-ogar-50/40' : 'border-gris-200'">
                            <div class="flex flex-wrap items-center gap-3">
                                <input type="hidden" name="enseignants[]" :value="membre.id">

                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-ogar-100 text-xs font-bold text-ogar-800"
                                      x-text="membre.initiales"></span>

                                <div class="min-w-0 flex-1">
                                    <div class="truncate font-semibold text-gris-900" x-text="membre.nom"></div>
                                    <div class="truncate text-xs text-gris-400">
                                        <span class="font-mono" x-text="membre.matricule"></span>
                                        &middot; <span x-text="membre.specialite"></span>
                                        &middot; <span x-text="membre.charge"></span> classe(s) au total
                                    </div>
                                </div>

                                <label class="flex cursor-pointer items-center gap-2 whitespace-nowrap text-sm text-gris-700">
                                    <input type="radio" name="principal" :value="membre.id" x-model="principal"
                                           class="h-4 w-4 cursor-pointer border-gris-300 text-ogar-600 focus:ring-ogar-600">
                                    Professeur principal
                                </label>

                                <button type="button" @click="retirer(membre.id)"
                                        class="cursor-pointer rounded-lg p-2 text-gris-400 transition-colors hover:bg-corail-50 hover:text-corail-600"
                                        title="Retirer de l’équipe">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Ce que ce membre couvre : sans cela on affecte à l'aveugle. --}}
                            <template x-if="matieres.length">
                                <div class="mt-2 flex flex-wrap items-center gap-1.5 pl-12">
                                    <template x-for="id in membre.matieres" :key="id">
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700"
                                              x-text="nomMatiere(id)"></span>
                                    </template>
                                    <span x-show="! membre.matieres.length" class="text-[11px] italic text-gris-400">
                                        Aucune matière du cycle rattachée
                                    </span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </form>

        {{-- Couverture des matières : pilotée par l'état courant du formulaire --}}
        @if (in_array($cycle, ['college', 'lycee'], true) && $matieresDuCycle->isNotEmpty())
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <h3 class="text-sm font-semibold text-gris-900">Matières du cycle</h3>
                    <span class="text-xs text-gris-400">
                        <span x-text="matieres.length - manquantes.length"></span> / <span x-text="matieres.length"></span> couvertes
                    </span>
                </div>

                <div class="p-5">
                    <p x-show="manquantes.length" class="mb-3 text-xs text-gris-500">
                        Cliquez sur une matière découverte pour chercher un enseignant qui la couvre.
                    </p>
                    <p x-show="! manquantes.length" x-cloak class="mb-3 text-xs text-emerald-700">
                        Toutes les matières du cycle sont couvertes par l’équipe.
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <template x-for="matiere in matieres" :key="matiere.id">
                            <template x-if="couvertes.has(matiere.id)">
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700"
                                      x-text="matiere.nom"></span>
                            </template>
                        </template>
                        <template x-for="matiere in manquantes" :key="matiere.id">
                            <button type="button" @click="chercherMatiere(matiere.nom)"
                                    class="cursor-pointer rounded-full border border-dashed border-gris-300 bg-white px-2.5 py-1 text-xs font-medium text-gris-500 transition-colors hover:border-ogar-400 hover:text-ogar-700">
                                <span x-text="matiere.nom"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Présences --------------------------------------------------------- --}}
    <div x-show="onglet === 'presences'" x-cloak class="space-y-4">

        @if ($presences['total'] === 0)
            <div class="carte">
                <div class="carte-entete">
                    <h3 class="text-sm font-semibold text-gris-900">Assiduité de la classe</h3>
                    <a href="{{ route('attendances.manage', $class->id) }}" class="bouton-primaire text-xs">Faire l’appel</a>
                </div>
                <div class="p-6">
                    <x-vide message="Aucun appel n’a encore été fait dans cette classe."/>
                    <p class="mt-2 text-center text-xs text-gris-400">
                        Le premier appel créera l’historique d’assiduité et alimentera les bulletins.
                    </p>
                </div>
            </div>
        @else
            {{-- Répartition globale --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($tuilesPresence as [$libelle, $valeur, $classeValeur, $classeLibelle])
                    <div class="carte p-4">
                        <div class="text-2xl font-bold {{ $classeValeur }}">{{ $valeur }}</div>
                        <div class="mt-0.5 text-xs font-semibold uppercase {{ $classeLibelle }}">{{ $libelle }}</div>
                        <div class="mt-1 text-[11px] text-gris-400">
                            {{ $presences['total'] > 0 ? round($valeur / $presences['total'] * 100) : 0 }}% des pointages
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Situation élève par élève : c'est là qu'on repère un décrochage --}}
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <div>
                        <h3 class="text-sm font-semibold text-gris-900">Assiduité par élève</h3>
                        <p class="mt-0.5 text-xs text-gris-400">
                            {{ $presences['total'] }} pointage(s) sur {{ $presences['seances']->count() }} séance(s) récentes
                            &middot; taux de présence de la classe {{ $presences['taux'] }}%
                        </p>
                    </div>
                    <a href="{{ route('attendances.manage', $class->id) }}" class="bouton-primaire text-xs">Faire l’appel</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="tableau">
                        <thead>
                            <tr>
                                <th>Élève</th>
                                <th class="text-center">Présent</th>
                                <th class="text-center">Absent</th>
                                <th class="text-center">Retard</th>
                                <th class="text-center">Justifié</th>
                                <th class="text-center">Taux</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($eleves as $eleve)
                                @php($ligne = $presences['par_eleve'][$eleve->id] ?? null)
                                @php($totalEleve = (int) ($ligne->total ?? 0))
                                @php($tauxEleve = $totalEleve > 0 ? round($ligne->presents / $totalEleve * 100) : null)
                                <tr>
                                    <td>
                                        <a href="{{ route('students.show', $eleve->id) }}"
                                           class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                            {{ $eleve->full_name }}
                                        </a>
                                        <div class="font-mono text-[11px] text-gris-400">{{ $eleve->student_id }}</div>
                                    </td>
                                    <td class="text-center text-gris-700">{{ $ligne->presents ?? 0 }}</td>
                                    <td class="text-center {{ ($ligne->absents ?? 0) > 0 ? 'font-semibold text-corail-700' : 'text-gris-400' }}">
                                        {{ $ligne->absents ?? 0 }}
                                    </td>
                                    <td class="text-center {{ ($ligne->retards ?? 0) > 0 ? 'font-semibold text-soleil-700' : 'text-gris-400' }}">
                                        {{ $ligne->retards ?? 0 }}
                                    </td>
                                    <td class="text-center text-gris-500">{{ $ligne->excuses ?? 0 }}</td>
                                    <td class="text-center">
                                        @if ($tauxEleve === null)
                                            <span class="text-xs text-gris-400">jamais pointé</span>
                                        @else
                                            <x-puce :couleur="$tauxEleve >= 90 ? 'emerald' : ($tauxEleve >= 75 ? 'amber' : 'rose')">
                                                {{ $tauxEleve }}%
                                            </x-puce>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-vide :colonnes="6" message="Aucun élève inscrit dans cette classe."/>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Séances récentes --}}
            <div class="carte overflow-hidden">
                <div class="carte-entete">
                    <h3 class="text-sm font-semibold text-gris-900">Dernières séances</h3>
                    <a href="{{ route('attendances.reports', $class->id) }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                        Rapport détaillé
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="tableau">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-center">Pointés</th>
                                <th class="text-center">Présents</th>
                                <th class="text-center">Absents</th>
                                <th class="text-center">Retards</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($presences['seances'] as $seance)
                                <tr>
                                    <td class="whitespace-nowrap font-medium text-gris-800">
                                        {{ \Carbon\Carbon::parse($seance->attendance_date)->translatedFormat('D j M Y') }}
                                    </td>
                                    <td class="text-center text-gris-600">{{ $seance->total }}</td>
                                    <td class="text-center text-emerald-700">{{ $seance->presents }}</td>
                                    <td class="text-center {{ $seance->absents > 0 ? 'font-semibold text-corail-700' : 'text-gris-400' }}">
                                        {{ $seance->absents }}
                                    </td>
                                    <td class="text-center {{ $seance->retards > 0 ? 'text-soleil-700' : 'text-gris-400' }}">
                                        {{ $seance->retards }}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('attendances.view', ['class' => $class->id, 'date' => $seance->attendance_date]) }}"
                                           class="bouton-mini">Consulter</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- Notes --------------------------------------------------------------- --}}
    <div x-show="onglet === 'notes'" x-cloak class="space-y-4">

        @if ($notes['total'] === 0)
            <div class="carte">
                <div class="carte-entete">
                    <h3 class="text-sm font-semibold text-gris-900">Notes de la classe</h3>
                    <a href="{{ route('grades.create') }}" class="bouton-primaire text-xs">Saisir des notes</a>
                </div>
                <div class="p-6">
                    <x-vide message="Aucune note n’a encore été saisie dans cette classe."/>
                    @if ($equipe->isEmpty())
                        <p class="mt-2 text-center text-xs text-corail-600">
                            Aucun enseignant n’est affecté : commencez par composer l’équipe pédagogique.
                        </p>
                    @endif
                </div>
            </div>
        @else
            {{-- Choix du trimestre : rechargement serveur, l'agrégat est calculé en base --}}
            <div class="carte flex flex-wrap items-center gap-3 p-4">
                <span class="text-xs font-semibold uppercase text-gris-500">Trimestre</span>
                @foreach ($notes['trimestres'] as $trimestre)
                    <a href="{{ route('classes.show', ['class' => $class->id, 'onglet' => 'notes', 'trimestre' => $trimestre]) }}"
                       class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors
                              {{ $notes['trimestre'] === $trimestre
                                 ? 'bg-ogar-600 text-white'
                                 : 'bg-gris-100 text-gris-600 hover:bg-gris-200' }}">
                        {{ $trimestre }}
                    </a>
                @endforeach

                <div class="ml-auto flex items-center gap-6 text-sm">
                    <div>
                        <span class="text-gris-400">Moyenne de classe</span>
                        <span class="ml-1 text-lg font-bold text-ogar-700">
                            {{ number_format($notes['moyenne'], 2, ',', ' ') }}<span class="text-xs font-normal text-gris-400">/20</span>
                        </span>
                    </div>
                    <div>
                        <span class="text-gris-400">Notes saisies</span>
                        <span class="ml-1 font-semibold text-gris-800">{{ $notes['total'] }}</span>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                {{-- Classement des élèves --}}
                <div class="carte overflow-hidden lg:col-span-2">
                    <div class="carte-entete">
                        <div>
                            <h3 class="text-sm font-semibold text-gris-900">Moyennes des élèves</h3>
                            <p class="mt-0.5 text-xs text-gris-400">
                                {{ $notes['evalues'] }} élève(s) évalué(s) sur {{ $eleves->count() }} inscrit(s)
                                &middot; {{ $notes['trimestre'] }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="tableau">
                            <thead>
                                <tr>
                                    <th class="w-14 text-center">Rang</th>
                                    <th>Élève</th>
                                    <th class="text-center">Notes</th>
                                    <th class="text-center">Moyenne</th>
                                    <th class="text-right">Bulletin</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Les élèves évalués passent devant, classés par moyenne. --}}
                                @php($elevesClasses = $eleves->sortByDesc(fn ($e) => $notes['par_eleve'][$e->id]['moyenne'] ?? -1))
                                @foreach ($elevesClasses as $eleve)
                                    @php($ligne = $notes['par_eleve'][$eleve->id] ?? null)
                                    <tr>
                                        <td class="text-center">
                                            @if ($ligne)
                                                <span class="font-bold text-gris-700">{{ $notes['rangs'][$eleve->id] }}</span>
                                            @else
                                                <span class="text-gris-300">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('students.show', $eleve->id) }}"
                                               class="font-medium text-gris-800 hover:text-ogar-700 hover:underline">
                                                {{ $eleve->full_name }}
                                            </a>
                                            <div class="font-mono text-[11px] text-gris-400">{{ $eleve->student_id }}</div>
                                        </td>
                                        <td class="text-center text-gris-500">{{ $ligne['notes'] ?? 0 }}</td>
                                        <td class="text-center">
                                            @if ($ligne)
                                                <x-puce :couleur="$ligne['moyenne'] >= 12 ? 'emerald' : ($ligne['moyenne'] >= 10 ? 'amber' : 'rose')">
                                                    {{ number_format($ligne['moyenne'], 2, ',', ' ') }}/20
                                                </x-puce>
                                            @else
                                                <span class="text-xs text-gris-400">non évalué</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('grades.manage-student', $eleve->id) }}" class="bouton-mini">Notes</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Moyennes par matière : révèle la matière qui décroche --}}
                <div class="carte overflow-hidden">
                    <div class="carte-entete">
                        <h3 class="text-sm font-semibold text-gris-900">Par matière</h3>
                    </div>
                    <div class="divide-y divide-gris-100">
                        @foreach ($notes['par_matiere'] as $matiere)
                            <div class="px-5 py-3">
                                <div class="flex items-baseline justify-between gap-3">
                                    <span class="truncate text-sm font-medium text-gris-800">{{ $matiere['matiere'] }}</span>
                                    <span class="shrink-0 text-sm font-bold {{ $matiere['moyenne'] >= 12 ? 'text-emerald-700' : ($matiere['moyenne'] >= 10 ? 'text-soleil-700' : 'text-corail-700') }}">
                                        {{ number_format($matiere['moyenne'], 2, ',', ' ') }}
                                    </span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gris-100">
                                    <div class="h-full rounded-full {{ $matiere['moyenne'] >= 12 ? 'bg-emerald-500' : ($matiere['moyenne'] >= 10 ? 'bg-soleil-500' : 'bg-corail-500') }}"
                                         style="width: {{ min(round($matiere['moyenne'] / 20 * 100), 100) }}%"></div>
                                </div>
                                <div class="mt-1 text-[11px] text-gris-400">{{ $matiere['notes'] }} note(s)</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Emploi du temps ---------------------------------------------------- --}}
    <div x-show="onglet === 'emploi'" x-cloak>
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h3 class="text-sm font-semibold text-gris-900">Créneaux de la semaine</h3>
                <a href="{{ route('schedules.index') }}" class="text-xs font-semibold text-ogar-600 hover:underline">
                    Module Emplois du temps
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Jour</th>
                            <th>Horaire</th>
                            <th>Matière</th>
                            <th>Enseignant</th>
                            <th>Salle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($creneaux as $creneau)
                            <tr>
                                <td class="whitespace-nowrap font-medium text-gris-800">
                                    {{ $jours[$creneau->day_of_week] ?? $creneau->day_of_week }}
                                </td>
                                <td class="whitespace-nowrap text-gris-600">
                                    {{ \Illuminate\Support\Str::substr($creneau->start_time, 0, 5) }}
                                    &ndash;
                                    {{ \Illuminate\Support\Str::substr($creneau->end_time, 0, 5) }}
                                </td>
                                <td>{{ $creneau->subject->name ?? $creneau->title ?? '—' }}</td>
                                <td>{{ $creneau->teacher?->full_name ?? '—' }}</td>
                                <td class="text-gris-500">{{ $creneau->room ?: '—' }}</td>
                            </tr>
                        @empty
                            <x-vide :colonnes="5" message="Aucun créneau planifié pour cette classe."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
