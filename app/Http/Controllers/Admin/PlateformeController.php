<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Support\Marque;
use App\Support\ParametresPlateforme;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\File;

/**
 * La plateforme elle-même : son identité, son comportement, sa corbeille.
 *
 * Le super administrateur ne gère pas les établissements de l'intérieur — cela
 * revient à leur propre administrateur. Il gère ce qui les surplombe : la
 * marque sous laquelle ils sont hébergés, les règles communes, et le rattrapage
 * des suppressions.
 */
class PlateformeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, $next) {
                abort_unless($request->user()?->role === 'superadmin', 403,
                    'La plateforme se gère depuis un compte super administrateur.');

                return $next($request);
            }),
        ];
    }

    /* ==================================================================
       Personnalisation
       ================================================================== */

    public function personnalisation()
    {
        return view('admin.plateforme.personnalisation', [
            'marque' => Marque::tous(),
            'logo' => Marque::logoUrl(),
        ]);
    }

    public function personnalisationSave(Request $request)
    {
        $donnees = $request->validate([
            'app_nom' => 'required|string|max:60',
            'app_slogan' => 'nullable|string|max:120',
            'login_titre' => 'nullable|string|max:120',
            'login_sous_titre' => 'nullable|string|max:400',
            'login_acces_rapide' => 'nullable|boolean',
            'pied_de_page' => 'nullable|string|max:200',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
        ], [], [
            'app_nom' => 'nom de l’application',
            'logo' => 'logo',
        ]);

        $reglages = [
            'app_nom' => $donnees['app_nom'],
            'app_slogan' => $donnees['app_slogan'] ?? '',
            'login_titre' => $donnees['login_titre'] ?? '',
            'login_sous_titre' => $donnees['login_sous_titre'] ?? '',
            'login_acces_rapide' => $request->boolean('login_acces_rapide'),
            'pied_de_page' => $donnees['pied_de_page'] ?? '',
        ];

        /*
         * Le logo est copié dans `public/marque` sous un nom fixe : l'ancien
         * est écrasé, rien ne s'accumule. Un jeton d'horodatage suffit à faire
         * relire le fichier par les navigateurs qui l'avaient en cache.
         */
        if ($request->hasFile('logo')) {
            $extension = $request->file('logo')->getClientOriginalExtension();
            $dossier = public_path('marque');

            File::ensureDirectoryExists($dossier);

            foreach (glob($dossier.'/logo.*') as $ancien) {
                File::delete($ancien);
            }

            $request->file('logo')->move($dossier, "logo.{$extension}");

            $reglages['logo'] = "marque/logo.{$extension}?v=".time();
        }

        Marque::enregistrer($reglages);

        return redirect()->route('admin.plateforme.personnalisation')
            ->with('success', 'La personnalisation de la plateforme est enregistrée.');
    }

    public function personnalisationReset()
    {
        foreach (glob(public_path('marque').'/logo.*') as $ancien) {
            File::delete($ancien);
        }

        Marque::reinitialiser();

        return redirect()->route('admin.plateforme.personnalisation')
            ->with('success', 'La personnalisation est revenue à ses valeurs d’origine.');
    }

    /* ==================================================================
       Paramètres
       ================================================================== */

    public function parametres()
    {
        return view('admin.plateforme.parametres', [
            'parametres' => ParametresPlateforme::tous(),
            'paginations' => ParametresPlateforme::PAGINATIONS,
        ]);
    }

    public function parametresSave(Request $request)
    {
        $donnees = $request->validate([
            'pagination_defaut' => 'required|integer|in:'.implode(',', ParametresPlateforme::PAGINATIONS),
            'corbeille_jours' => 'required|integer|min:1|max:365',
            'message_connexion' => 'nullable|string|max:300',
        ], [], [
            'pagination_defaut' => 'taille des listes',
            'corbeille_jours' => 'durée de conservation',
        ]);

        ParametresPlateforme::enregistrer([
            'pagination_defaut' => (int) $donnees['pagination_defaut'],
            'corbeille_jours' => (int) $donnees['corbeille_jours'],
            'inscriptions_en_ligne' => $request->boolean('inscriptions_en_ligne'),
            'connexion_ouverte' => $request->boolean('connexion_ouverte'),
            'message_connexion' => $donnees['message_connexion'] ?? null,
        ]);

        return redirect()->route('admin.plateforme.parametres')
            ->with('success', 'Les paramètres de la plateforme sont enregistrés.');
    }

    /* ==================================================================
       Corbeille
       ================================================================== */

    /**
     * Ce que la corbeille sait reprendre, et comment le nommer.
     */
    public static function entites(): array
    {
        return [
            'eleves' => [
                'libelle' => 'Élèves', 'singulier' => 'L’élève', 'modele' => Student::class,
                'nom' => fn ($m) => $m->last_name.' '.$m->first_name,
                'reference' => fn ($m) => $m->student_id,
            ],
            'enseignants' => [
                'libelle' => 'Enseignants', 'singulier' => 'L’enseignant', 'modele' => Teacher::class,
                'nom' => fn ($m) => $m->last_name.' '.$m->first_name,
                'reference' => fn ($m) => $m->employee_id,
            ],
            'classes' => [
                'libelle' => 'Classes', 'singulier' => 'La classe', 'modele' => SchoolClass::class,
                'nom' => fn ($m) => $m->name,
                'reference' => fn ($m) => $m->getSafeLevelName(),
            ],
            'parents' => [
                'libelle' => 'Parents', 'singulier' => 'Le parent', 'modele' => ParentModel::class,
                'nom' => fn ($m) => $m->last_name.' '.$m->first_name,
                'reference' => fn ($m) => $m->phone,
            ],
            'matieres' => [
                'libelle' => 'Matières', 'singulier' => 'La matière', 'modele' => Subject::class,
                'nom' => fn ($m) => $m->name,
                'reference' => fn ($m) => $m->code,
            ],
        ];
    }

    public function corbeille(Request $request)
    {
        $entites = self::entites();

        $ouverte = $request->input('entite');
        $ouverte = array_key_exists((string) $ouverte, $entites) ? $ouverte : array_key_first($entites);

        /*
         * Le super administrateur surplombe les établissements : la corbeille
         * les traverse tous, sinon les suppressions faites dans une école
         * qu'il n'a pas sélectionnée lui resteraient invisibles.
         */
        $compteurs = collect($entites)->map(fn ($def) => $def['modele']::onlyTrashed()
            ->tousEtablissements()
            ->count());

        $definition = $entites[$ouverte];

        $lignes = $definition['modele']::onlyTrashed()
            ->tousEtablissements()
            ->orderByDesc('deleted_at')
            ->paginate(ParametresPlateforme::pagination($request->input('per_page')))
            ->withQueryString();

        // Qui a supprimé quoi : la colonne ne porte qu'un identifiant.
        $auteurs = \App\Models\User::whereIn('id', $lignes->pluck('deleted_by')->filter()->unique())
            ->pluck('name', 'id');

        $ecoles = \App\Models\School::pluck('name', 'id');

        return view('admin.plateforme.corbeille', [
            'entites' => $entites,
            'ouverte' => $ouverte,
            'definition' => $definition,
            'compteurs' => $compteurs,
            'lignes' => $lignes,
            'auteurs' => $auteurs,
            'ecoles' => $ecoles,
            'jours' => ParametresPlateforme::joursDeCorbeille(),
        ]);
    }

    public function restaurer(string $entite, int $id)
    {
        $definition = self::entites()[$entite] ?? abort(404);

        $ligne = $definition['modele']::onlyTrashed()
            ->tousEtablissements()
            ->findOrFail($id);

        $ligne->restore();

        return back()->with('success', $definition['singulier'].' a été restauré.');
    }

    public function supprimer(string $entite, int $id)
    {
        $definition = self::entites()[$entite] ?? abort(404);

        $ligne = $definition['modele']::onlyTrashed()
            ->tousEtablissements()
            ->findOrFail($id);

        $ligne->forceDelete();

        return back()->with('success', $definition['singulier'].' a été supprimé définitivement.');
    }

    /**
     * Vider ce qui a dépassé la durée de conservation.
     *
     * Le vidage ne touche pas ce qui vient d'être supprimé : c'est justement
     * ce qu'on est le plus susceptible de vouloir reprendre.
     */
    public function vider(string $entite)
    {
        $definition = self::entites()[$entite] ?? abort(404);
        $limite = now()->subDays(ParametresPlateforme::joursDeCorbeille());

        $lignes = $definition['modele']::onlyTrashed()
            ->tousEtablissements()
            ->where('deleted_at', '<', $limite)
            ->get();

        foreach ($lignes as $ligne) {
            $ligne->forceDelete();
        }

        return back()->with('success', $lignes->count() > 0
            ? $lignes->count().' élément(s) supprimé(s) définitivement.'
            : 'Rien n’a dépassé la durée de conservation : la corbeille est inchangée.');
    }
}
