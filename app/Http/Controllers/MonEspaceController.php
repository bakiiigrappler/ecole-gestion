<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentGrade;

/**
 * L'espace d'un élève : ce qui le concerne, et rien d'autre.
 *
 * Un compte élève n'administre rien. Il a son tableau de bord, ses notes, son
 * emploi du temps, ses absences et sa fiche — toujours filtrés sur lui-même,
 * jamais sur sa classe ni sur l'établissement.
 *
 * La scolarité ne figure nulle part : ce qui est dû, ce qui est réglé et les
 * reçus regardent le parent, qui les a sur son portail. Un élève n'a pas à
 * savoir si ses frais sont à jour.
 *
 * Ces rubriques étaient d'abord empilées sur une seule page : on ne trouvait
 * rien, et il n'y avait ni tableau de bord ni espace de notes à proprement
 * parler. Chacune a désormais sa page et son entrée dans le menu.
 */
class MonEspaceController extends Controller
{
    /**
     * L'élève derrière le compte connecté.
     *
     * Le lien passe par `students.user_id`, posé à l'ouverture du compte.
     */
    private function eleve(): Student
    {
        $eleve = Student::where('user_id', auth()->id())->first();

        abort_unless($eleve, 403, 'Ce compte n’est rattaché à aucun élève.');

        return $eleve;
    }

    private function annee(): ?AcademicYear
    {
        return AcademicYear::where('is_current', true)->first();
    }

    /**
     * Son inscription de l'année : sa classe et son niveau.
     */
    private function inscription(Student $eleve)
    {
        return $eleve->enrollments()
            ->with(['schoolClass.level', 'academicYear'])
            ->where('status', 'active')
            ->latest()
            ->first();
    }

    /**
     * Ses notes de l'année, une fois pour toutes.
     */
    private function notesDeLAnnee(Student $eleve, ?AcademicYear $annee)
    {
        return StudentGrade::with(['subject:id,name', 'teacher:id,first_name,last_name'])
            ->where('student_id', $eleve->id)
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
            ->where('max_score', '>', 0)
            ->orderBy('term')
            ->orderByDesc('created_at')
            ->get();
    }

    /** Une note quelconque, ramenée sur 20. */
    private function sur20($note): float
    {
        return $note->score / $note->max_score * 20;
    }

    private function assiduite(Student $eleve, ?AcademicYear $annee)
    {
        $pointages = Attendance::where('student_id', $eleve->id)
            ->when($annee, fn ($q) => $q->whereBetween('attendance_date', [
                $annee->start_date,
                $annee->end_date,
            ]))
            ->orderByDesc('attendance_date')
            ->get(['attendance_date', 'status', 'reason', 'justified']);

        $chiffres = [
            'total' => $pointages->count(),
            'present' => $pointages->where('status', 'present')->count(),
            'absent' => $pointages->where('status', 'absent')->count(),
            'late' => $pointages->where('status', 'late')->count(),
            'excused' => $pointages->where('status', 'excused')->count(),
        ];

        $chiffres['taux'] = $chiffres['total'] > 0
            ? round($chiffres['present'] / $chiffres['total'] * 100)
            : null;

        return [$chiffres, $pointages];
    }

    /* ------------------------------------------------------------------
       Le tableau de bord : ce qu'il faut savoir en un coup d'œil.
       ------------------------------------------------------------------ */
    public function index()
    {
        $eleve = $this->eleve();
        $annee = $this->annee();
        $inscription = $this->inscription($eleve);

        $notes = $this->notesDeLAnnee($eleve, $annee);
        [$assiduite, $pointages] = $this->assiduite($eleve, $annee);

        $parMatiere = $notes
            ->groupBy('subject_id')
            ->map(fn ($lot) => [
                'matiere' => $lot->first()->subject->name ?? 'Matière supprimée',
                'notes' => $lot->count(),
                'moyenne' => round($lot->avg(fn ($n) => $this->sur20($n)), 2),
            ])
            ->sortByDesc('moyenne')
            ->values();

        // Les cours du jour : ce qu'il a devant lui en ouvrant la page.
        $aujourdHui = now();

        $duJour = $inscription
            ? Schedule::with(['subject:id,name', 'teacher:id,first_name,last_name'])
                ->where('class_id', $inscription->class_id)
                ->where('day_of_week', $aujourdHui->dayOfWeekIso)
                ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
                ->orderBy('start_time')
                ->get()
            : collect();

        return view('mon-espace.index', [
            'eleve' => $eleve,
            'inscription' => $inscription,
            'annee' => $annee,
            'parMatiere' => $parMatiere->take(6),
            'moyenneGenerale' => $notes->isNotEmpty()
                ? round($notes->avg(fn ($n) => $this->sur20($n)), 2)
                : null,
            'nombreDeNotes' => $notes->count(),
            'assiduite' => $assiduite,
            'dernieresAbsences' => $pointages
                ->whereIn('status', ['absent', 'late', 'excused'])->take(5)->values(),
            'duJour' => $duJour,
            'aujourdHui' => $aujourdHui,
        ]);
    }

    /* ------------------------------------------------------------------
       Mes notes : le détail, et le bulletin quand il y a de quoi l'éditer.
       ------------------------------------------------------------------ */
    public function notes()
    {
        $eleve = $this->eleve();
        $annee = $this->annee();
        $inscription = $this->inscription($eleve);

        $notes = $this->notesDeLAnnee($eleve, $annee);

        /*
         * Le detail, trimestre par trimestre : une moyenne annuelle ne dit pas
         * a un eleve ou il progresse. Et c'est le trimestre qui commande le
         * bulletin : il n'est editable que la ou il y a des notes.
         */
        $parTrimestre = $notes
            ->groupBy('term')
            ->map(fn ($lot, $trimestre) => [
                'trimestre' => $trimestre ?: 'Sans trimestre',
                'notes' => $lot->count(),
                'matieres' => $lot->pluck('subject_id')->unique()->count(),
                'moyenne' => round($lot->avg(fn ($n) => $this->sur20($n)), 2),
            ])
            ->sortKeys()
            ->values();

        // Une ligne par matière et par trimestre : c'est la lecture d'un
        // bulletin, et c'est celle que l'élève cherche.
        $matieres = $notes
            ->groupBy('subject_id')
            ->map(function ($lot) {
                $parTerme = $lot->groupBy('term')
                    ->map(fn ($t) => round($t->avg(fn ($n) => $this->sur20($n)), 2));

                return [
                    'matiere' => $lot->first()->subject->name ?? 'Matière supprimée',
                    'enseignant' => $lot->first()->teacher
                        ? $lot->first()->teacher->last_name.' '.$lot->first()->teacher->first_name
                        : null,
                    'notes' => $lot->count(),
                    'trimestres' => $parTerme,
                    'moyenne' => round($lot->avg(fn ($n) => $this->sur20($n)), 2),
                ];
            })
            ->sortBy('matiere')
            ->values();

        return view('mon-espace.notes', [
            'eleve' => $eleve,
            'inscription' => $inscription,
            'annee' => $annee,
            'notes' => $notes,
            'parTrimestre' => $parTrimestre,
            'matieres' => $matieres,
            'termes' => $notes->pluck('term')->filter()->unique()->sort()->values(),
            'moyenneGenerale' => $notes->isNotEmpty()
                ? round($notes->avg(fn ($n) => $this->sur20($n)), 2)
                : null,
        ]);
    }

    /* ------------------------------------------------------------------
       Mon emploi du temps
       ------------------------------------------------------------------ */
    public function emploiDuTemps()
    {
        $eleve = $this->eleve();
        $annee = $this->annee();
        $inscription = $this->inscription($eleve);

        $creneaux = $inscription
            ? Schedule::with(['subject:id,name', 'teacher:id,first_name,last_name'])
                ->where('class_id', $inscription->class_id)
                ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
            : collect();

        return view('mon-espace.emploi-du-temps', compact('eleve', 'inscription', 'annee', 'creneaux'));
    }

    /* ------------------------------------------------------------------
       Mes absences
       ------------------------------------------------------------------ */
    public function absences()
    {
        $eleve = $this->eleve();
        $annee = $this->annee();
        $inscription = $this->inscription($eleve);

        [$assiduite, $pointages] = $this->assiduite($eleve, $annee);

        return view('mon-espace.absences', [
            'eleve' => $eleve,
            'inscription' => $inscription,
            'annee' => $annee,
            'assiduite' => $assiduite,
            'absences' => $pointages->whereIn('status', ['absent', 'late', 'excused'])->values(),
        ]);
    }

    /* ------------------------------------------------------------------
       Ma fiche élève : le document officiel, à télécharger.
       ------------------------------------------------------------------ */
    public function fiche()
    {
        $eleve = $this->eleve()->load('parents');
        $annee = $this->annee();
        $inscription = $this->inscription($eleve);

        [$assiduite] = $this->assiduite($eleve, $annee);

        $notes = $this->notesDeLAnnee($eleve, $annee);

        return view('mon-espace.fiche', [
            'eleve' => $eleve,
            'inscription' => $inscription,
            'annee' => $annee,
            'assiduite' => $assiduite,
            'moyenneGenerale' => $notes->isNotEmpty()
                ? round($notes->avg(fn ($n) => $this->sur20($n)), 2)
                : null,
        ]);
    }
}
