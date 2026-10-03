<?php

namespace App\Support\Orientation;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentGrade;

/**
 * Le profil scolaire d'un élève, lu sur ses notes réelles.
 *
 * C'est ici que l'orientation cesse d'être un questionnaire. Ailleurs, l'élève
 * recopie ses moyennes de mémoire — ou celles qu'il aurait aimé avoir — et le
 * conseil qui en sort ne vaut pas mieux que la saisie. L'établissement, lui,
 * tient déjà les notes : le profil se calcule sur elles, trimestre par
 * trimestre, et personne n'a rien à ressaisir.
 *
 * Trois moyennes comptent : la générale, celle des disciplines scientifiques et
 * celle des disciplines littéraires. L'écart entre les deux dernières dit la
 * dominante ; la générale dit ce qui est permis.
 */
class ProfilEleve
{
    /**
     * Les disciplines, rangées par famille.
     *
     * Le rapprochement se fait sur le nom en minuscules sans accent : un
     * établissement écrit « Sciences Physiques », un autre « Sciences
     * physiques », un troisième « Physique-Chimie ».
     */
    private const FAMILLES = [
        'sciences' => [
            'mathematiques', 'mathematiques appliquees', 'mathematiques specialisees',
            'sciences physiques', 'physique', 'physique-chimie', 'chimie',
            'sciences de la vie et de la terre', 'svt', 'biologie', 'biologie approfondie',
            'technologie', 'technologie industrielle', 'dessin technique', 'informatique',
        ],
        'lettres' => [
            'francais', 'litterature', 'anglais', 'espagnol', 'allemand', 'latin', 'grec',
            'histoire-geographie', 'histoire', 'geographie', 'philosophie',
            'education civique', 'education a la citoyennete',
        ],
        'gestion' => [
            'sciences economiques et sociales', 'economie', 'comptabilite', 'gestion',
        ],
    ];

    /** En deçà, le passage en seconde n'est pas acquis. */
    public const SEUIL_PASSAGE = 10.0;

    /** Au-delà, la dominante est franche et non une simple tendance. */
    private const ECART_MARQUE = 1.5;

    private const ECART_TENDANCE = 0.5;

    /**
     * Le relevé d'un élève pour une année.
     *
     * @return array{
     *     notes: int, generale: ?float, sciences: ?float, lettres: ?float, gestion: ?float,
     *     par_matiere: array<int, array{matiere: string, moyenne: float, notes: int}>,
     *     ecart: ?float, profil: string, libelle: string, lecture: string
     * }
     */
    public static function etablir(Student $eleve, ?AcademicYear $annee = null): array
    {
        $annee ??= AcademicYear::where('is_current', true)->first();

        $notes = StudentGrade::with('subject:id,name,coefficient')
            ->where('student_id', $eleve->id)
            ->when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))
            ->where('max_score', '>', 0)
            ->get();

        if ($notes->isEmpty()) {
            return self::releveVide();
        }

        // Chaque note est ramenée sur 20 : les devoirs ne sont pas tous notés
        // sur le même barème.
        $parMatiere = $notes
            ->filter(fn ($n) => $n->subject)
            ->groupBy('subject_id')
            ->map(fn ($lot) => [
                'matiere' => $lot->first()->subject->name,
                'coefficient' => (float) ($lot->first()->subject->coefficient ?: 1),
                'moyenne' => round($lot->avg(fn ($n) => $n->score / $n->max_score * 20), 2),
                'notes' => $lot->count(),
            ])
            ->values();

        if ($parMatiere->isEmpty()) {
            return self::releveVide();
        }

        $moyenneDe = function ($lot) {
            $poids = $lot->sum('coefficient');

            return $poids > 0
                ? round($lot->sum(fn ($m) => $m['moyenne'] * $m['coefficient']) / $poids, 2)
                : null;
        };

        $generale = $moyenneDe($parMatiere);
        $sciences = $moyenneDe($parMatiere->filter(fn ($m) => self::famille($m['matiere']) === 'sciences'));
        $lettres = $moyenneDe($parMatiere->filter(fn ($m) => self::famille($m['matiere']) === 'lettres'));
        $gestion = $moyenneDe($parMatiere->filter(fn ($m) => self::famille($m['matiere']) === 'gestion'));

        $ecart = ($sciences !== null && $lettres !== null) ? round($sciences - $lettres, 2) : null;

        [$profil, $libelle, $lecture] = self::lire($generale, $sciences, $lettres, $ecart);

        return [
            'notes' => $notes->count(),
            'generale' => $generale,
            'sciences' => $sciences,
            'lettres' => $lettres,
            'gestion' => $gestion,
            'par_matiere' => $parMatiere->sortByDesc('moyenne')->values()->all(),
            'ecart' => $ecart,
            'profil' => $profil,
            'libelle' => $libelle,
            'lecture' => $lecture,
        ];
    }

    /**
     * Ce que les trois moyennes disent, en une phrase.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private static function lire(?float $generale, ?float $sciences, ?float $lettres, ?float $ecart): array
    {
        if ($generale === null) {
            return ['indetermine', 'Profil à établir', 'Aucune note n’a encore été saisie pour cette année.'];
        }

        if ($generale < self::SEUIL_PASSAGE) {
            return [
                'fragile',
                'Scolarité fragile',
                'La moyenne générale de '.self::nombre($generale).'/20 ne permet pas, en l’état, '
                    .'un passage assuré en seconde. Le conseil de classe tranchera ; une année '
                    .'de consolidation vaut mieux qu’un lycée subi.',
            ];
        }

        if ($ecart === null) {
            return [
                'equilibre',
                'Profil équilibré',
                'Les notes disponibles ne permettent pas de distinguer une dominante : la voie '
                    .'générale laisse le choix ouvert.',
            ];
        }

        if ($ecart >= self::ECART_MARQUE && $sciences >= 13) {
            return [
                'scientifique',
                'Profil scientifique affirmé',
                'Les disciplines scientifiques ('.self::nombre($sciences).'/20) devancent nettement '
                    .'les littéraires ('.self::nombre($lettres).'/20). Les séries C et D sont à portée.',
            ];
        }

        if ($ecart <= -self::ECART_MARQUE && $lettres >= 13) {
            return [
                'litteraire',
                'Profil littéraire affirmé',
                'Les disciplines littéraires ('.self::nombre($lettres).'/20) devancent nettement '
                    .'les scientifiques ('.self::nombre($sciences).'/20). Les séries A1, A2 et B sont à portée.',
            ];
        }

        if ($ecart >= self::ECART_TENDANCE) {
            return [
                'tendance_scientifique',
                'Tendance scientifique',
                'Un léger avantage aux sciences ('.self::nombre($sciences).' contre '
                    .self::nombre($lettres).'). La série C reste exigeante ; la série D est plus sûre.',
            ];
        }

        if ($ecart <= -self::ECART_TENDANCE) {
            return [
                'tendance_litteraire',
                'Tendance littéraire',
                'Un léger avantage aux lettres ('.self::nombre($lettres).' contre '
                    .self::nombre($sciences).'). Les séries A et B conviennent ; la B demande des mathématiques tenues.',
            ];
        }

        return [
            'equilibre',
            'Profil équilibré',
            'Sciences et lettres se tiennent ('.self::nombre($sciences).' contre '.self::nombre($lettres)
                .'). Le choix se fera sur le goût et le projet, non sur les notes.',
        ];
    }

    /**
     * La famille d'une discipline, ou null si elle n'entre dans aucune.
     *
     * L'éducation physique et les arts ne pèsent dans aucune dominante : une
     * bonne note en sport ne dit rien d'une vocation scientifique.
     */
    public static function famille(string $matiere): ?string
    {
        $reduit = self::reduire($matiere);

        foreach (self::FAMILLES as $famille => $disciplines) {
            foreach ($disciplines as $discipline) {
                if ($reduit === $discipline || str_contains($reduit, $discipline)) {
                    return $famille;
                }
            }
        }

        return null;
    }

    private static function reduire(string $texte): string
    {
        $sansAccent = strtr(mb_strtolower(trim($texte)), [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
        ]);

        return preg_replace('/\s+/', ' ', $sansAccent);
    }

    public static function nombre(?float $valeur): string
    {
        return $valeur === null ? '—' : number_format($valeur, 2, ',', ' ');
    }

    private static function releveVide(): array
    {
        return [
            'notes' => 0,
            'generale' => null,
            'sciences' => null,
            'lettres' => null,
            'gestion' => null,
            'par_matiere' => [],
            'ecart' => null,
            'profil' => 'indetermine',
            'libelle' => 'Profil à établir',
            'lecture' => 'Aucune note n’a encore été saisie pour cette année : le profil se calcule sur elles.',
        ];
    }
}
