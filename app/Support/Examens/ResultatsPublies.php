<?php

namespace App\Support\Examens;

use App\Models\Examen;
use App\Models\ExamenCandidat;
use Illuminate\Support\Facades\Storage;

/**
 * Les résultats proclamés, servis sans toucher la base.
 *
 * Un jour de proclamation n'est pas un trafic ordinaire : tout le pays arrive
 * dans la même minute et pose la même question. Si chaque visite interroge la
 * base et fabrique une page, le nombre de connexions simultanées qu'un serveur
 * tient se compte en centaines — c'est la raison pour laquelle les portails
 * nationaux tombent à quelques milliers de visiteurs.
 *
 * Or un résultat proclamé ne change plus. On ne le calcule donc pas à la
 * demande : on l'écrit une fois, à la proclamation, dans un fichier par
 * candidat, nommé d'après son numéro. La consultation n'est plus qu'une lecture
 * de fichier — ce qu'un serveur sait faire des milliers de fois par seconde, et
 * ce qu'un CDN sait faire sans serveur du tout.
 *
 * Deux index accompagnent les fiches :
 *   — `index.json`, le résumé de la session, pour la page d'accueil ;
 *   — `noms.json`, l'annuaire nom → numéro, pour qui a perdu sa convocation.
 *     C'est la requête qui coûte le plus cher en base ; préparée d'avance, elle
 *     ne coûte plus rien.
 */
class ResultatsPublies
{
    private const DISQUE = 'local';

    private const RACINE = 'resultats';

    /**
     * Écrit l'ensemble des fiches d'une session.
     *
     * @return int Le nombre de fiches écrites
     */
    public static function reconstruire(Examen $examen): int
    {
        $dossier = self::dossier($examen);

        Storage::disk(self::DISQUE)->deleteDirectory($dossier);

        $annuaire = [];
        $bilan = ['admis' => 0, 'second_tour' => 0, 'ajourne' => 0, 'absent' => 0];
        $ecrites = 0;

        ExamenCandidat::where('examen_id', $examen->id)
            ->with('centre:id,nom,ville')
            ->chunkById(500, function ($candidats) use ($examen, $dossier, &$annuaire, &$bilan, &$ecrites) {
                foreach ($candidats as $candidat) {
                    Storage::disk(self::DISQUE)->put(
                        $dossier.'/'.self::clef($candidat->numero_candidat).'.json',
                        json_encode(self::fiche($examen, $candidat), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                    );

                    $annuaire[] = [
                        'n' => $candidat->numero_candidat,
                        'c' => self::reduire($candidat->nom.' '.$candidat->prenom),
                    ];

                    if (isset($bilan[$candidat->statut])) {
                        $bilan[$candidat->statut]++;
                    }

                    $ecrites++;
                }
            });

        Storage::disk(self::DISQUE)->put(
            $dossier.'/index.json',
            json_encode([
                'examen' => $examen->intitule_complet,
                'type' => $examen->libelle_type,
                'annee' => $examen->annee,
                'proclamation' => optional($examen->proclamation)->toDateString(),
                'candidats' => $ecrites,
                'bilan' => $bilan,
                'taux_reussite' => $ecrites > 0 ? round($bilan['admis'] / $ecrites * 100, 2) : 0,
                'genere_le' => now()->toDateTimeString(),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        Storage::disk(self::DISQUE)->put(
            $dossier.'/noms.json',
            json_encode($annuaire, JSON_UNESCAPED_UNICODE)
        );

        return $ecrites;
    }

    /**
     * La fiche d'un candidat, telle qu'elle sera lue.
     *
     * Elle ne porte que ce qui se dit publiquement : le résultat, la mention,
     * le centre. Pas la date de naissance — elle sert à prouver qu'on est bien
     * le candidat, la publier reviendrait à donner la clef avec la serrure —
     * ni le détail des notes, qui appartient au relevé remis à l'intéressé.
     */
    private static function fiche(Examen $examen, ExamenCandidat $candidat): array
    {
        return [
            'numero' => $candidat->numero_candidat,
            'nom' => $candidat->nom,
            'prenom' => $candidat->prenom,
            // L'empreinte de la date de naissance : la saisie se vérifie sans
            // que la date figure en clair dans un fichier public.
            'naissance' => $candidat->date_naissance
                ? hash('sha256', $candidat->date_naissance->toDateString())
                : null,
            'examen' => $examen->intitule_complet,
            'serie' => $candidat->serie,
            'centre' => $candidat->centre?->nom,
            'ville' => $candidat->centre?->ville,
            'etablissement' => $candidat->etablissement_origine,
            'statut' => $candidat->statut,
            'decision' => ExamenCandidat::STATUTS[$candidat->statut] ?? $candidat->statut,
            'mention' => $candidat->mention,
            'moyenne' => $candidat->moyenne,
            'proclamation' => optional($examen->proclamation)->toDateString(),
        ];
    }

    /**
     * Lit une fiche. Aucune requête : un fichier, et c'est tout.
     */
    public static function lire(Examen $examen, string $numero): ?array
    {
        $chemin = self::dossier($examen).'/'.self::clef($numero).'.json';

        if (! Storage::disk(self::DISQUE)->exists($chemin)) {
            return null;
        }

        return json_decode(Storage::disk(self::DISQUE)->get($chemin), true);
    }

    public static function resume(Examen $examen): ?array
    {
        $chemin = self::dossier($examen).'/index.json';

        if (! Storage::disk(self::DISQUE)->exists($chemin)) {
            return null;
        }

        return json_decode(Storage::disk(self::DISQUE)->get($chemin), true);
    }

    /**
     * Retrouver un numéro à partir d'un nom, dans l'annuaire préparé.
     *
     * @return array<int, array{numero: string, nom: string}>
     */
    public static function chercherParNom(Examen $examen, string $terme, int $limite = 10): array
    {
        $chemin = self::dossier($examen).'/noms.json';

        if (! Storage::disk(self::DISQUE)->exists($chemin) || mb_strlen(trim($terme)) < 3) {
            return [];
        }

        $recherche = self::reduire($terme);
        $trouves = [];

        foreach (json_decode(Storage::disk(self::DISQUE)->get($chemin), true) ?? [] as $entree) {
            if (str_contains($entree['c'], $recherche)) {
                $trouves[] = ['numero' => $entree['n'], 'nom' => $entree['c']];

                if (count($trouves) >= $limite) {
                    break;
                }
            }
        }

        return $trouves;
    }

    /**
     * La date de naissance saisie est-elle celle du candidat ?
     */
    public static function naissanceConcorde(array $fiche, ?string $date): bool
    {
        if (empty($fiche['naissance'])) {
            return true;
        }

        return $date !== null && hash_equals($fiche['naissance'], hash('sha256', $date));
    }

    private static function dossier(Examen $examen): string
    {
        return self::RACINE.'/'.$examen->id;
    }

    /** Un numéro devient un nom de fichier sûr. */
    private static function clef(string $numero): string
    {
        return preg_replace('/[^A-Za-z0-9\-]/', '', strtoupper(trim($numero)));
    }

    private static function reduire(string $texte): string
    {
        $sansAccent = strtr(mb_strtolower(trim($texte)), [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
        ]);

        return preg_replace('/\s+/', ' ', $sansAccent);
    }
}
