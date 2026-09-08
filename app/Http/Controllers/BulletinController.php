<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentGrade;
use Illuminate\Http\Request;

class BulletinController extends Controller
{
    /**
     * Affiche toutes les classes avec leurs niveaux
     */
    public function index(Request $request)
    {
        $annee = AcademicYear::where('is_current', true)->first();

        // La liste des classes est filtrable et paginee : 37 classes d'un
        // seul tenant, on ne retrouvait plus la sienne.
        $requete = SchoolClass::query()
            ->with(['level:id,name,code,cycle'])
            ->withCount(['students as effectif' => function ($q) use ($annee) {
                if ($annee) {
                    $q->where('enrollments.academic_year_id', $annee->id);
                }

                $q->where('enrollments.status', 'active');
            }]);

        /*
         * Un enseignant n'edite pas les bulletins de l'etablissement : il suit
         * ceux de ses classes. La liste s'y limite, et la page lui presente un
         * onglet par classe plutot qu'une pagination.
         */
        $estEnseignant = \App\Support\PerimetreEnseignant::estEnseignant();
        $mesClasses = collect();

        if ($estEnseignant) {
            $siennes = \App\Support\PerimetreEnseignant::classes() ?: [0];
            $requete->whereIn('id', $siennes);

            $mesClasses = SchoolClass::with('level:id,name,code,cycle')
                ->whereIn('id', $siennes)
                ->orderBy('name')
                ->get();

            /*
             * La navigation par cycle et par niveau menerait hors de son
             * perimetre. Il entre directement dans une de ses classes, et les
             * onglets de cette page-la lui permettent d'en changer.
             */
            $ouverte = $request->input('classe');
            $ouverte = $mesClasses->contains('id', (int) $ouverte)
                ? (int) $ouverte
                : $mesClasses->first()?->id;

            if ($ouverte) {
                return redirect()->route('bulletins.class', $ouverte);
            }
        }

        if ($terme = trim((string) $request->input('recherche'))) {
            $requete->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($terme) . '%']);
        }

        if ($classeId = $request->input('classe')) {
            $requete->where('id', $classeId);
        }

        if ($cycle = $request->input('cycle')) {
            $requete->whereHas('level', fn ($q) => $q->where('cycle', $cycle));
        }

        $classes = $requete->orderBy('name')->paginate(10)->withQueryString();

        // Nombre d'eleves deja notes, pour les seules classes de la page.
        $notes = StudentGrade::whereIn('class_id', $classes->pluck('id'))
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
            ->selectRaw('class_id, count(distinct student_id) as notes')
            ->groupBy('class_id')
            ->pluck('notes', 'class_id');

        // Les compteurs par cycle portent sur l'ensemble, pas sur la page.
        $repartition = SchoolClass::with('level:id,cycle')
            ->when($estEnseignant, fn ($q) => $q->whereIn('id', \App\Support\PerimetreEnseignant::classes() ?: [0]))
            ->get()
            ->groupBy(fn ($c) => $c->level->cycle ?? 'primaire')
            ->map->count();

        $listeClasses = SchoolClass::with('level:id,cycle')
            ->when($estEnseignant, fn ($q) => $q->whereIn('id', \App\Support\PerimetreEnseignant::classes() ?: [0]))
            ->orderBy('name')
            ->get(['id', 'name', 'level_id']);

        // Niveaux, pour la navigation par cycle
        $levels = Level::with(['classes' => function ($query) {
                $query->select('id', 'name', 'level_id');
            }])
            ->active()
            ->orderBy('order')
            ->get();

        $levelsByCycle = [
            'preprimaire' => $levels->where('cycle', 'preprimaire'),
            'primaire' => $levels->where('cycle', 'primaire'),
            'college' => $levels->where('cycle', 'college'),
            'lycee' => $levels->where('cycle', 'lycee'),
        ];

        return view('bulletin.fich', compact(
            'classes',
            'notes',
            'repartition',
            'listeClasses',
            'levelsByCycle',
            'annee',
            'estEnseignant',
            'mesClasses'
        ));
    }

    /**
     * Affiche les notes d'un étudiant
     */
    public function studentGrades($studentId)
    {
        $student = Student::with(['grades.subject', 'grades.teacher'])
            ->findOrFail($studentId);
            
        return view('bulletin.student-grades', compact('student'));
    }

    /**
     * Affiche les détails d'une classe spécifique
     */
    public function show($id)
    {
        $classe = SchoolClass::with('level')->findOrFail($id);
        $annee = AcademicYear::where('is_current', true)->first();

        // Un enseignant n'ouvre que les bulletins de ses classes, et la page
        // lui donne un onglet par classe pour passer de l'une a l'autre.
        $estEnseignant = \App\Support\PerimetreEnseignant::estEnseignant();
        $mesClasses = collect();

        if ($estEnseignant) {
            $siennes = \App\Support\PerimetreEnseignant::classes();

            abort_unless(in_array($classe->id, $siennes, true), 403,
                'Cette classe ne fait pas partie de votre emploi du temps.');

            $mesClasses = SchoolClass::whereIn('id', $siennes ?: [0])
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        // Les eleves de l'annee en cours, pas tous ceux passes par la classe.
        // `when()` sur une relation many-to-many perd `wherePivot` : la
        // condition doit etre posee directement sur la relation.
        $relation = $classe->students()->wherePivot('status', 'active');

        if ($annee) {
            $relation->wherePivot('academic_year_id', $annee->id);
        }

        $eleves = $relation->orderBy('last_name')->orderBy('first_name')->get();

        // Moyenne generale et volume de notes par eleve : sans cela, la page
        // ne disait pas quels bulletins etaient reellement editables.
        $chiffres = StudentGrade::whereIn('student_id', $eleves->pluck('id'))
            ->where('class_id', $classe->id)
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
            ->where('max_score', '>', 0)
            ->where('score', '>=', 0)
            ->selectRaw('student_id, count(*) as notes, avg(score / max_score * 20) as moyenne')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        return view('bulletin.classe-details', compact(
            'classe', 'eleves', 'chiffres', 'annee', 'estEnseignant', 'mesClasses'
        ));
    }

    /**
     * Affiche les classes par niveau
     */
    public function byLevel($levelId)
    {
        $level = Level::findOrFail($levelId);
        $annee = AcademicYear::where('is_current', true)->first();

        $classes = $level->classes()
            ->withCount(['students as effectif' => fn ($q) => $q
                ->when($annee, fn ($r) => $r->where('enrollments.academic_year_id', $annee->id))
                ->where('enrollments.status', 'active')])
            ->orderBy('name')
            ->get();

        // Nombre d'eleves deja notes par classe : c'est ce qui dit si les
        // bulletins du niveau peuvent etre edites.
        $notes = StudentGrade::whereIn('class_id', $classes->pluck('id'))
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
            ->selectRaw('class_id, count(distinct student_id) as notes')
            ->groupBy('class_id')
            ->pluck('notes', 'class_id');

        return view('bulletin.by-level', compact('level', 'classes', 'notes', 'annee'));
    }

    /**
     * Affiche les classes par cycle
     */
    public function byCycle($cycle)
    {
        $levels = Level::with('classes')
            ->where('cycle', $cycle)
            ->active()
            ->orderBy('order')
            ->get();
            
        $cycleName = [
            'preprimaire' => 'Préprimaire',
            'primaire' => 'Primaire',
            'college' => 'Collège',
            'lycee' => 'Lycée'
        ][$cycle] ?? 'Cycle inconnu';
            
        return view('bulletin.by-cycle', compact('levels', 'cycle', 'cycleName'));
    }
}
