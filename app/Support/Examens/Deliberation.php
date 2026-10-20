<?php

namespace App\Support\Examens;

use App\Models\Examen;
use App\Models\ExamenCandidat;
use App\Models\ExamenNote;
use Illuminate\Support\Facades\DB;

/**
 * Le calcul des résultats : moyennes, décisions, mentions.
 *
 * Trois règles, et elles ne souffrent pas l'à-peu-près parce qu'elles décident
 * d'une année de vie :
 *
 *   1. **Chaque note est ramenée sur 20** avant d'être pondérée. Une épreuve
 *      notée sur 40 et une autre sur 20 ne s'additionnent pas telles quelles.
 *   2. **L'épreuve facultative ne compte que pour ses points au-dessus de la
 *      moyenne**, et ne pèse pas dans le diviseur : la compter comme les
 *      autres ferait baisser la moyenne de qui l'a choisie, ce qui est
 *      exactement l'inverse de son objet.
 *   3. **L'absence vaut zéro**, mais l'épreuve non corrigée ne vaut rien du
 *      tout : tant qu'une copie n'a pas de note, le candidat n'est pas
 *      délibérable — le dire plutôt que de calculer une moyenne fausse.
 */
class Deliberation
{
    /**
     * Calcule et enregistre le résultat de chaque candidat d'une session.
     *
     * @return array{candidats: int, admis: int, second_tour: int, ajournes: int, incomplets: int}
     */
    public static function prononcer(Examen $examen): array
    {
        $examen->loadMissing('epreuves');

        $epreuves = $examen->epreuves->keyBy('id');
        $diviseur = $examen->totalDesCoefficients();

        $notes = ExamenNote::where('examen_id', $examen->id)
            ->get()
            ->groupBy('candidat_id');

        $bilan = ['candidats' => 0, 'admis' => 0, 'second_tour' => 0, 'ajournes' => 0, 'incomplets' => 0];

        ExamenCandidat::where('examen_id', $examen->id)
            ->chunkById(200, function ($candidats) use ($epreuves, $notes, $diviseur, $examen, &$bilan) {
                foreach ($candidats as $candidat) {
                    $bilan['candidats']++;

                    if ($candidat->statut === 'exclu') {
                        continue;
                    }

                    $resultat = self::calculer($candidat, $notes->get($candidat->id) ?? collect(), $epreuves, $diviseur);

                    if ($resultat['incomplet']) {
                        $bilan['incomplets']++;
                    }

                    $statut = self::decider($examen, $candidat, $resultat);
                    $bilan[match ($statut) {
                        'admis' => 'admis',
                        'second_tour' => 'second_tour',
                        default => 'ajournes',
                    }]++;

                    $candidat->forceFill([
                        'total_points' => $resultat['points'],
                        'moyenne' => $resultat['moyenne'],
                        'statut' => $statut,
                        'mention' => $statut === 'admis'
                            ? ExamenCandidat::mentionPour($resultat['moyenne'])
                            : null,
                    ])->save();
                }
            });

        return $bilan;
    }

    /**
     * Le détail d'un candidat : points, moyenne, et ce qui manque.
     *
     * @return array{points: ?float, moyenne: ?float, incomplet: bool, manquantes: int}
     */
    public static function calculer($candidat, $notesDuCandidat, $epreuves, float $diviseur): array
    {
        $points = 0.0;
        $manquantes = 0;

        $parEpreuve = $notesDuCandidat->keyBy('epreuve_id');

        foreach ($epreuves as $epreuve) {
            $note = $parEpreuve->get($epreuve->id);

            if (! $note || ($note->note === null && ! $note->absent)) {
                // Une épreuve obligatoire sans note empêche la délibération ;
                // une facultative non passée ne gêne personne.
                if (! $epreuve->facultative) {
                    $manquantes++;
                }

                continue;
            }

            $sur20 = $note->absent ? 0.0 : (float) $epreuve->sur20($note->note);

            if ($epreuve->facultative) {
                // Seuls les points au-dessus de 10, et jamais en négatif.
                $points += max(0, $sur20 - 10) * $epreuve->coefficient;

                continue;
            }

            $points += $sur20 * $epreuve->coefficient;
        }

        $moyenne = $diviseur > 0 ? round($points / $diviseur, 2) : null;

        return [
            'points' => round($points, 2),
            'moyenne' => $moyenne,
            'incomplet' => $manquantes > 0,
            'manquantes' => $manquantes,
        ];
    }

    /**
     * La décision : admis, second tour, ou ajourné.
     *
     * Un candidat absent à toutes les épreuves n'est pas « ajourné » mais
     * absent : la nuance compte pour l'inscription de l'année suivante.
     */
    private static function decider(Examen $examen, ExamenCandidat $candidat, array $resultat): string
    {
        if ($candidat->statut === 'absent') {
            return 'absent';
        }

        if ($resultat['moyenne'] === null) {
            return 'ajourne';
        }

        if ($resultat['moyenne'] >= $examen->moyenne_admission) {
            return 'admis';
        }

        if ($examen->moyenne_second_tour && $resultat['moyenne'] >= $examen->moyenne_second_tour) {
            return 'second_tour';
        }

        return 'ajourne';
    }

    /**
     * Où en est la correction : combien de copies notées, par épreuve.
     *
     * @return array<int, array{epreuve: string, attendues: int, saisies: int, part: int}>
     */
    public static function avancement(Examen $examen): array
    {
        $attendues = ExamenCandidat::where('examen_id', $examen->id)
            ->whereNotIn('statut', ['exclu'])
            ->count();

        $saisies = ExamenNote::where('examen_id', $examen->id)
            ->select('epreuve_id', DB::raw('count(*) as nombre'))
            ->groupBy('epreuve_id')
            ->pluck('nombre', 'epreuve_id');

        return $examen->epreuves->map(fn ($epreuve) => [
            'epreuve' => $epreuve->intitule,
            'attendues' => $attendues,
            'saisies' => (int) ($saisies[$epreuve->id] ?? 0),
            'part' => $attendues > 0 ? (int) round(($saisies[$epreuve->id] ?? 0) / $attendues * 100) : 0,
        ])->all();
    }
}
