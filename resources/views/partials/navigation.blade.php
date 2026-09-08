@php
    /*
     * Menu lateral. Les rubriques sont déclarées en données plutôt qu'en
     * balisage : chaque profil voit les siennes, et l'état actif se déduit du
     * motif de route. Les rubriques vides ne sont pas rendues.
     */
    $utilisateur = auth()->user();
    $role = $utilisateur?->role;

    $estEnseignant   = $role === 'teacher';
    $estSecretaire   = $role === 'secretary';
    $estAdmin        = in_array($role, ['admin', 'superadmin'], true);
    $estSuperAdmin   = $role === 'superadmin';

    /*
     * Le superadmin surplombe les etablissements : tant qu'il ne s'est placé
     * dans aucun, les modules de scolarité n'ont pas d'objet. Ils réapparaissent
     * dès qu'il entre dans un établissement.
     */
    $dansUnEtablissement = ! $estSuperAdmin || \App\Support\EcoleCourante::id() !== null;

    // Le secondaire et le primaire n'ont pas le même système d'évaluation :
    // les rubriques suivent la configuration de l'établissement.
    $parametres  = $schoolSettings ?? null;
    $aPreprimaire = $parametres?->has_preprimary ?? true;
    $aPrimaire    = $parametres?->has_primary ?? true;
    $aSecondaire  = $parametres?->has_secondary ?? true;

    $estEleve = $role === 'student';
    $estParent = $role === 'parent';

    $rubriques = [
        [
            'titre' => 'Mon espace',
            'visible' => $estEleve,
            'liens' => [
                ['libelle' => 'Tableau de bord',   'route' => 'mon-espace',                   'motif' => 'mon-espace'],
                ['libelle' => 'Mes notes',         'route' => 'mon-espace.notes',             'motif' => 'mon-espace.notes'],
                ['libelle' => 'Mon emploi du temps', 'route' => 'mon-espace.emploi-du-temps', 'motif' => 'mon-espace.emploi-du-temps'],
                ['libelle' => 'Mes absences',      'route' => 'mon-espace.absences',          'motif' => 'mon-espace.absences'],
                ['libelle' => 'Mes reçus',         'route' => 'mon-espace.paiements',         'motif' => 'mon-espace.paiements'],
                ['libelle' => 'Ma fiche élève',    'route' => 'mon-espace.fiche',             'motif' => 'mon-espace.fiche'],
            ],
        ],
        [
            'titre' => 'Mon espace parent',
            'visible' => $estParent,
            'liens' => [
                ['libelle' => 'Tableau de bord', 'route' => 'parent-portal.dashboard', 'motif' => 'parent-portal.dashboard'],
                ['libelle' => 'Mes enfants',   'route' => 'parent-portal.children',        'motif' => 'parent-portal.children'],
                ['libelle' => 'Mes paiements', 'route' => 'parent-portal.payment-history', 'motif' => 'parent-portal.payment-history'],
                ['libelle' => 'Mon profil',    'route' => 'parent-portal.profile',         'motif' => 'parent-portal.profile'],
            ],
        ],
        [
            'titre' => 'Scolarité',
            'visible' => ($estAdmin || $estSecretaire) && $dansUnEtablissement,
            'liens' => [
                ['libelle' => 'Élèves',      'route' => 'students.index',  'motif' => 'students.*'],
                ['libelle' => 'Enseignants', 'route' => 'teachers.index',  'motif' => 'teachers.*'],
                ['libelle' => 'Parents',     'route' => 'parents.index',   'motif' => 'parents.*'],
                ['libelle' => 'Classes',     'route' => 'classes.index',   'motif' => 'classes.*'],
                ['libelle' => 'Matières',    'route' => 'subjects.index',  'motif' => 'subjects.*', 'visible' => $estAdmin],
                ['libelle' => 'Séries (lycée)', 'route' => 'series.index', 'motif' => 'series.*',   'visible' => $estAdmin && $aSecondaire],
            ],
        ],
        [
            'titre' => 'Mes classes',
            'visible' => $estEnseignant && $dansUnEtablissement,
            'liens' => [
                ['libelle' => 'Classes',           'route' => 'classes.index',   'motif' => 'classes.*'],
                ['libelle' => 'Matières',          'route' => 'subjects.index',  'motif' => 'subjects.*'],
                ['libelle' => 'Emplois du temps',  'route' => 'schedules.index', 'motif' => 'schedules.*'],
                ['libelle' => 'Liste des élèves',  'route' => 'students.index',  'motif' => 'students.*'],
            ],
        ],
        [
            'titre' => 'Inscriptions',
            'visible' => ($estAdmin || $estSecretaire) && $dansUnEtablissement,
            'liens' => [
                ['libelle' => 'Inscriptions', 'route' => 'enrollments.index',            'motif' => 'enrollments.index'],
                ['libelle' => 'En attente',   'route' => 'enrollments.pending-students', 'motif' => 'enrollments.pending-students'],
            ],
        ],
        [
            'titre' => 'Évaluations',
            'visible' => ($estAdmin || $estEnseignant) && $dansUnEtablissement,
            'liens' => [
                ['libelle' => 'Notes (secondaire)',      'route' => 'grades.index',                   'motif' => 'grades.*',                   'visible' => $aSecondaire],
                ['libelle' => 'Compétences (primaire)',  'route' => 'competency-evaluations.index',   'motif' => 'competency-evaluations.*',   'visible' => $aPrimaire && $estAdmin],
                ['libelle' => 'Notes préprimaire',       'route' => 'pre-primary-evaluations.index',  'motif' => 'pre-primary-evaluations.*',  'visible' => $aPreprimaire && $estAdmin],
                ['libelle' => 'Bulletins',               'route' => 'bulletins.index',                'motif' => 'bulletins*'],
                ['libelle' => 'Présences',               'route' => 'attendances.index',              'motif' => 'attendances.*'],
                ['libelle' => 'Emplois du temps',        'route' => 'schedules.index',                'motif' => 'schedules.*',                'visible' => $estAdmin],
            ],
        ],
        [
            'titre' => 'Finances',
            'visible' => ($estAdmin || $estSecretaire) && $dansUnEtablissement,
            'liens' => [
                ['libelle' => 'Frais scolaires', 'route' => 'fees.index',     'motif' => 'fees.*'],
                ['libelle' => 'Paiements',       'route' => 'payments.index', 'motif' => 'payments.*'],
            ],
        ],
        [
            'titre' => 'Rapports',
            'visible' => ($estAdmin || $estSecretaire) && $dansUnEtablissement,
            'liens' => [
                ['libelle' => 'Statistiques', 'route' => 'statistics', 'motif' => 'statistics*'],
            ],
        ],
        [
            'titre' => 'Plateforme',
            'visible' => $estSuperAdmin,
            'liens' => [
                ['libelle' => 'Établissements',   'route' => 'admin.schools.index',                'motif' => 'admin.schools.*'],
                ['libelle' => 'Personnalisation',  'route' => 'admin.plateforme.personnalisation',  'motif' => 'admin.plateforme.personnalisation*'],
                ['libelle' => 'Paramètres',        'route' => 'admin.plateforme.parametres',        'motif' => 'admin.plateforme.parametres*'],
                ['libelle' => 'Corbeille',         'route' => 'admin.plateforme.corbeille',         'motif' => 'admin.plateforme.corbeille*'],
            ],
        ],
        [
            'titre' => 'Administration',
            'visible' => $estAdmin,
            'liens' => [
                /*
                 * Administration d'une école : elle appartient à son propre
                 * administrateur. Le superadmin n'y accède qu'après s'être
                 * placé dans un établissement — hors de là, ces pages ne
                 * désignent aucune école en particulier.
                 */
                ['libelle' => 'Paramètres généraux',       'route' => 'admin.settings.index',        'motif' => 'admin.settings.*',           'visible' => $dansUnEtablissement],
                ['libelle' => 'Paramètres établissement',  'route' => 'admin.school-settings.index', 'motif' => 'admin.school-settings.*',    'visible' => $dansUnEtablissement],
                ['libelle' => 'Comptes utilisateurs',      'route' => 'admin.users.index',           'motif' => 'admin.users.*',              'visible' => $dansUnEtablissement],

                // Serveur : commun a tous les etablissements.
                ['libelle' => 'Informations système',      'route' => 'admin.system-info',           'motif' => 'admin.system-info'],
                ['libelle' => 'Sécurité',                  'route' => 'admin.security',              'motif' => 'admin.security'],
                ['libelle' => 'Maintenance',               'route' => 'admin.maintenance',           'motif' => 'admin.maintenance'],
            ],
        ],
    ];
@endphp

<nav class="space-y-6 px-3 py-5">

    {{-- Le tableau de bord reste accessible a tous les profils --}}
    {{-- L'eleve et le parent ont leur propre tableau de bord, dans leur
         rubrique : ce lien generique les y renverrait par une redirection, et
         doublonnerait l'entree qu'ils ont deja. --}}
    @unless ($estEleve || $estParent)
    @php($surLeTableauDeBord = request()->routeIs('dashboard'))

    <a href="{{ route('dashboard') }}"
       @if ($surLeTableauDeBord) aria-current="page" @endif
       class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors duration-200
              focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-400
              {{ $surLeTableauDeBord
                    ? 'bg-ogar-600 font-semibold text-white shadow-sm'
                    : 'text-gris-300 hover:bg-white/10 hover:text-white' }}">
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
        </svg>
        Tableau de bord
    </a>
    @endunless

    @foreach ($rubriques as $rubrique)
        @continue(! $rubrique['visible'])

        @php($liens = array_filter($rubrique['liens'], fn ($lien) => $lien['visible'] ?? true))
        @continue(empty($liens))

        <div>
            <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.12em] text-ogar-300">
                {{ $rubrique['titre'] }}
            </div>

            <ul class="space-y-0.5">
                @foreach ($liens as $lien)
                    @php($actif = request()->routeIs($lien['motif']))

                    <li>
                        <a href="{{ route($lien['route']) }}"
                           @if ($actif) aria-current="page" @endif
                           class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors duration-200
                                  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ogar-400
                                  {{ $actif
                                        ? 'bg-ogar-600 font-semibold text-white shadow-sm'
                                        : 'text-gris-300 hover:bg-white/10 hover:text-white' }}">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $actif ? 'bg-white' : 'bg-ogar-400/60' }}"></span>
                            <span class="truncate">{{ $lien['libelle'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
