<?php

namespace App\Support\Orientation;

/**
 * Ce que le profil permet, voie par voie et filière par filière.
 *
 * Le conseil ne tranche pas à la place de l'élève : il dit ce qui est ouvert,
 * ce qui est atteignable et ce qui ne l'est pas, et pourquoi. Une filière
 * barrée sans explication se contourne par la rumeur ; une filière expliquée se
 * discute avec un professeur.
 *
 * Trois états pour chaque filière : **conseillée** quand le profil et la
 * moyenne concordent, **ouverte** quand elle est permise sans être indiquée,
 * **sur concours** quand la moyenne n'atteint pas le seuil d'inscription
 * directe — car le concours, lui, reste ouvert à tous.
 */
class Conseil
{
    /**
     * Les voies, dans l'ordre où elles conviennent à ce profil.
     *
     * @param array $profil Le relevé rendu par ProfilEleve::etablir()
     * @param bool $gouteALaPratique L'élève se dit plus à l'aise dans le concret
     * @return array<int, array{voie: string, libelle: string, detail: string, couleur: string,
     *                          recommandee: bool, raison: string}>
     */
    public static function voies(array $profil, bool $gouteALaPratique = false): array
    {
        $generale = $profil['generale'];
        $sciences = $profil['sciences'];
        $dominante = $profil['profil'];

        $voies = [];

        foreach (VoiesApresTroisieme::VOIES as $cle => $voie) {
            [$recommandee, $raison] = self::juger($cle, $dominante, $generale, $sciences, $gouteALaPratique);

            $voies[] = [
                'voie' => $cle,
                'libelle' => $voie['libelle'],
                'detail' => $voie['detail'],
                'couleur' => $voie['couleur'],
                'recommandee' => $recommandee,
                'raison' => $raison,
            ];
        }

        // Les voies conseillées d'abord : c'est l'ordre dans lequel on lit.
        usort($voies, fn ($a, $b) => ($b['recommandee'] <=> $a['recommandee']));

        return $voies;
    }

    /** @return array{0: bool, 1: string} */
    private static function juger(string $voie, string $dominante, ?float $generale, ?float $sciences, bool $pratique): array
    {
        if ($generale === null) {
            return [false, 'À déterminer une fois les notes saisies.'];
        }

        $fragile = $generale < ProfilEleve::SEUIL_PASSAGE;

        return match ($voie) {
            'generale' => $fragile
                ? [false, 'La moyenne générale n’assure pas encore le passage en seconde générale.']
                : (in_array($dominante, ['scientifique', 'litteraire', 'tendance_scientifique', 'tendance_litteraire', 'equilibre'], true) && ! $pratique
                    ? [true, 'Les résultats permettent la seconde générale, et le profil y désigne une série.']
                    : [false, 'Accessible, mais l’élève se dit plus à l’aise dans le concret.']),

            'technique_industrielle' => $pratique && ($sciences ?? 0) >= 10
                ? [true, 'Des bases scientifiques tenues ('.ProfilEleve::nombre($sciences).'/20) et un goût pour la pratique : la technique industrielle s’impose.']
                : [false, ($sciences ?? 0) >= 10
                    ? 'Ouverte : les sciences suivent, il reste à confirmer le goût de l’atelier.'
                    : 'Les disciplines scientifiques sont encore trop justes pour cette voie.'],

            'technique_gestion' => $pratique && $generale >= 11
                ? [true, 'Un profil équilibré et un goût pour la pratique : la gestion mène vite à un métier.']
                : [false, $generale >= 11
                    ? 'Ouverte, notamment si le commerce ou la comptabilité attirent.'
                    : 'La moyenne générale reste un peu juste pour l’inscription directe ; le concours demeure ouvert.'],

            'professionnelle' => $fragile || ($pratique && $generale < 12)
                ? [true, 'Un métier appris tôt, et une insertion rapide : c’est une voie choisie, pas une voie subie.']
                : [false, 'Ouverte à tout élève qui veut un métier plutôt que des années d’études.'],

            default => [false, ''],
        };
    }

    /**
     * Les filières d'une voie, avec ce que la moyenne y ouvre.
     *
     * @return array<int, array{nom: string, moyenne_min: float, debouches: string,
     *                          etat: string, mention: string}>
     */
    public static function filieres(string $voie, array $profil): array
    {
        $generale = $profil['generale'];
        $dominante = $profil['profil'];

        $correspondances = [
            'scientifique' => 'scientifique',
            'tendance_scientifique' => 'scientifique',
            'litteraire' => 'litteraire',
            'tendance_litteraire' => 'litteraire',
            'equilibre' => 'equilibre',
        ];

        $profilCourt = $correspondances[$dominante] ?? null;

        return array_map(function (array $filiere) use ($generale, $profilCourt) {
            $seuil = (float) $filiere['moyenne_min'];
            $conseillee = $profilCourt && in_array($profilCourt, $filiere['profils'] ?? [], true);

            if ($generale === null) {
                $etat = 'ouverte';
                $mention = 'Moyenne à établir.';
            } elseif ($generale >= $seuil) {
                $etat = $conseillee ? 'conseillee' : 'ouverte';
                $mention = $seuil > 0
                    ? 'Inscription directe possible : la moyenne demandée est de '.rtrim(rtrim(number_format($seuil, 1, ',', ' '), '0'), ',').'/20.'
                    : 'Aucune moyenne minimale : l’admission se fait sur la motivation.';
            } else {
                $etat = 'concours';
                $mention = 'Inscription directe à partir de '.rtrim(rtrim(number_format($seuil, 1, ',', ' '), '0'), ',')
                    .'/20 ; en deçà, le concours d’entrée reste ouvert.';
            }

            return [
                'nom' => $filiere['nom'],
                'moyenne_min' => $seuil,
                'debouches' => $filiere['debouches'] ?? '',
                'etat' => $etat,
                'mention' => $mention,
            ];
        }, VoiesApresTroisieme::filieres($voie));
    }

    /**
     * Le mode d'admission qu'ouvre la moyenne pour une filière donnée.
     */
    public static function modeAdmission(?float $generale, ?array $filiere): string
    {
        if (! $filiere) {
            return 'dossier';
        }

        return ($generale !== null && $generale >= (float) $filiere['moyenne_min'])
            ? 'moyenne'
            : 'concours';
    }

    public static function libelleModeAdmission(?string $mode): string
    {
        return match ($mode) {
            'moyenne' => 'Inscription directe sur moyenne',
            'concours' => 'Concours d’entrée',
            'dossier' => 'Étude de dossier',
            default => 'À préciser',
        };
    }
}
