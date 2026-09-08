<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrePrimaryCompetencySeeder extends Seeder
{
    public function run()
    {
        // Supprimer les données existantes
        DB::table('pre_primary_competencies')->truncate();

        $competencies = [
            // Domain: Compétences transversales, faces d'être
            [
                'code' => 'CT_01',
                'name' => 'S\'intègre dans la vie de la classe',
                'description' => 'Capacité à s\'adapter et à participer activement à la vie collective de la classe',
                'domain' => 'Compétences transversales, faces d\'être',
                'sort_order' => 1
            ],
            [
                'code' => 'CT_02',
                'name' => 'Respecte les consignes',
                'description' => 'Capacité à comprendre et à suivre les instructions données',
                'domain' => 'Compétences transversales, faces d\'être',
                'sort_order' => 2
            ],
            [
                'code' => 'CT_03',
                'name' => 'Résout les problèmes de la vie quotidienne',
                'description' => 'Capacité à faire face aux situations du quotidien',
                'domain' => 'Compétences transversales, faces d\'être',
                'sort_order' => 3
            ],
            [
                'code' => 'CT_04',
                'name' => 'Assume l\'espace',
                'description' => 'Capacité à se repérer et à utiliser l\'espace de manière appropriée',
                'domain' => 'Compétences transversales, faces d\'être',
                'sort_order' => 4
            ],
            [
                'code' => 'CT_05',
                'name' => 'Partage, écoute avec autrui',
                'description' => 'Capacité à communiquer et à partager avec les autres',
                'domain' => 'Compétences transversales, faces d\'être',
                'sort_order' => 5
            ],
            [
                'code' => 'CT_06',
                'name' => 'Recherche la voix et la qualité de la présentation du travail',
                'description' => 'Capacité à soigner son travail et sa présentation',
                'domain' => 'Compétences transversales, faces d\'être',
                'sort_order' => 6
            ],
            [
                'code' => 'CT_07',
                'name' => 'Fait ce qu\'il faut: Compléter, polir',
                'description' => 'Capacité à terminer et à perfectionner son travail',
                'domain' => 'Compétences transversales, faces d\'être',
                'sort_order' => 7
            ],
            [
                'code' => 'CT_08',
                'name' => 'S\'applique avec objectif',
                'description' => 'Capacité à travailler avec concentration et détermination',
                'domain' => 'Compétences transversales, faces d\'être',
                'sort_order' => 8
            ],

            // Domain: Langue orale
            [
                'code' => 'LO_01',
                'name' => 'S\'exprime par des mots',
                'description' => 'Capacité à utiliser des mots pour communiquer',
                'domain' => 'Langue orale',
                'sort_order' => 1
            ],
            [
                'code' => 'LO_02',
                'name' => 'S\'exprime par des phrases',
                'description' => 'Capacité à construire des phrases complètes',
                'domain' => 'Langue orale',
                'sort_order' => 2
            ],
            [
                'code' => 'LO_03',
                'name' => 'Prend la parole en groupe',
                'description' => 'Capacité à s\'exprimer devant le groupe',
                'domain' => 'Langue orale',
                'sort_order' => 3
            ],
            [
                'code' => 'LO_04',
                'name' => 'Écoute l\'expression orale',
                'description' => 'Capacité à écouter attentivement les autres',
                'domain' => 'Langue orale',
                'sort_order' => 4
            ],
            [
                'code' => 'LO_05',
                'name' => 'Donne des réponses adaptées aux questions',
                'description' => 'Capacité à répondre de manière pertinente',
                'domain' => 'Langue orale',
                'sort_order' => 5
            ],

            // Domain: Production Lecture
            [
                'code' => 'PL_01',
                'name' => 'Lit des productions écrites concernant des graphies',
                'description' => 'Capacité à reconnaître et lire des écrits simples',
                'domain' => 'Production Lecture',
                'sort_order' => 1
            ],
            [
                'code' => 'PL_02',
                'name' => 'Distingue le livre correctement',
                'description' => 'Capacité à manipuler un livre dans le bon sens',
                'domain' => 'Production Lecture',
                'sort_order' => 2
            ],
            [
                'code' => 'PL_03',
                'name' => 'Donne une information trouvée grâce aux illustrations',
                'description' => 'Capacité à comprendre une image',
                'domain' => 'Production Lecture',
                'sort_order' => 3
            ],
            [
                'code' => 'PL_04',
                'name' => 'Reconnaît son prénom',
                'description' => 'Capacité à identifier son prénom écrit',
                'domain' => 'Production Lecture',
                'sort_order' => 4
            ],
            [
                'code' => 'PL_05',
                'name' => 'Reconstitue un mot',
                'description' => 'Capacité à recomposer un mot à partir de lettres',
                'domain' => 'Production Lecture',
                'sort_order' => 5
            ],

            // Domain: Graphomotricité Écriture
            [
                'code' => 'GE_01',
                'name' => 'Copie des lettres',
                'description' => 'Capacité à reproduire des lettres',
                'domain' => 'Graphomotricité Écriture',
                'sort_order' => 1
            ],
            [
                'code' => 'GE_02',
                'name' => 'Copie quelques mots avec modèle',
                'description' => 'Capacité à copier des mots simples',
                'domain' => 'Graphomotricité Écriture',
                'sort_order' => 2
            ],
            [
                'code' => 'GE_03',
                'name' => 'Sait reconnaître les outils scripteurs',
                'description' => 'Capacité à identifier les outils pour écrire',
                'domain' => 'Graphomotricité Écriture',
                'sort_order' => 3
            ],
            [
                'code' => 'GE_04',
                'name' => 'Sait reproduire le graphisme appris',
                'description' => 'Capacité à reproduire des tracés',
                'domain' => 'Graphomotricité Écriture',
                'sort_order' => 4
            ],
            [
                'code' => 'GE_05',
                'name' => 'Sait écrire entre deux lignes',
                'description' => 'Capacité à respecter les lignes d\'écriture',
                'domain' => 'Graphomotricité Écriture',
                'sort_order' => 5
            ],

            // Domain: Logico-Mathématiques et Précopto-motrice
            [
                'code' => 'LM_01',
                'name' => 'Écrit la suite des nombres jusqu\'à...',
                'description' => 'Capacité à écrire une suite numérique',
                'domain' => 'Logico-Mathématiques et Précopto-motrice',
                'sort_order' => 1
            ],
            [
                'code' => 'LM_02',
                'name' => 'Reconnaît des formes géométriques simples',
                'description' => 'Capacité à identifier les formes de base',
                'domain' => 'Logico-Mathématiques et Précopto-motrice',
                'sort_order' => 2
            ],
            [
                'code' => 'LM_03',
                'name' => 'Récite la chaîne numérique',
                'description' => 'Capacité à compter oralement',
                'domain' => 'Logico-Mathématiques et Précopto-motrice',
                'sort_order' => 3
            ],
            [
                'code' => 'LM_04',
                'name' => 'Reconnaît les couleurs',
                'description' => 'Capacité à nommer les couleurs',
                'domain' => 'Logico-Mathématiques et Précopto-motrice',
                'sort_order' => 4
            ],
            [
                'code' => 'LM_05',
                'name' => 'Comprend des notions perceptives/motrices du trimestre',
                'description' => 'Capacité à comprendre les concepts spatiaux',
                'domain' => 'Logico-Mathématiques et Précopto-motrice',
                'sort_order' => 5
            ],

            // Domain: Espace et temps
            [
                'code' => 'ET_01',
                'name' => 'Établit un événement ayant un lien avec le jour de la semaine',
                'description' => 'Capacité à situer un événement dans la semaine',
                'domain' => 'Espace et temps',
                'sort_order' => 1
            ],
            [
                'code' => 'ET_02',
                'name' => 'Connaît la succession des jours',
                'description' => 'Capacité à réciter les jours de la semaine',
                'domain' => 'Espace et temps',
                'sort_order' => 2
            ],
            [
                'code' => 'ET_03',
                'name' => 'Établit la succession des mois',
                'description' => 'Capacité à connaître l\'ordre des mois',
                'domain' => 'Espace et temps',
                'sort_order' => 3
            ],
            [
                'code' => 'ET_04',
                'name' => 'Connaît et utilise l\'hier, aujourd\'hui, demain',
                'description' => 'Capacité à se repérer dans le temps proche',
                'domain' => 'Espace et temps',
                'sort_order' => 4
            ],

            // Domain: EPS
            [
                'code' => 'EP_01',
                'name' => 'Expression corporelle',
                'description' => 'Capacité à s\'exprimer par le mouvement',
                'domain' => 'EPS',
                'sort_order' => 1
            ],
            [
                'code' => 'EP_02',
                'name' => 'Est créatif (vin) pour le Dessin libre',
                'description' => 'Capacité à créer librement par le dessin',
                'domain' => 'EPS',
                'sort_order' => 2
            ],
            [
                'code' => 'EP_03',
                'name' => 'Colle proprement',
                'description' => 'Capacité à utiliser la colle correctement',
                'domain' => 'EPS',
                'sort_order' => 3
            ],
            [
                'code' => 'EP_04',
                'name' => 'Découpe en suivant un tracé',
                'description' => 'Capacité à découper avec précision',
                'domain' => 'EPS',
                'sort_order' => 4
            ],
            [
                'code' => 'EP_05',
                'name' => 'Coupe sans dépassé',
                'description' => 'Capacité à découper sans dépasser',
                'domain' => 'EPS',
                'sort_order' => 5
            ],
            [
                'code' => 'EP_06',
                'name' => 'Schéma corporel',
                'description' => 'Connaissance des parties du corps',
                'domain' => 'EPS',
                'sort_order' => 6
            ],

            // Domain: Engagement Moral
            [
                'code' => 'EM_01',
                'name' => 'Fait preuve de valeurs morales',
                'description' => 'Capacité à montrer des comportements moraux',
                'domain' => 'Engagement Moral',
                'sort_order' => 1
            ],
            [
                'code' => 'EM_02',
                'name' => 'Respecte les règles de vie commune',
                'description' => 'Capacité à vivre en harmonie avec les autres',
                'domain' => 'Engagement Moral',
                'sort_order' => 2
            ],
            [
                'code' => 'EM_03',
                'name' => 'Fait preuve d\'autonomie',
                'description' => 'Capacité à agir de manière indépendante',
                'domain' => 'Engagement Moral',
                'sort_order' => 3
            ],

            // Domain: Découverte du monde
            [
                'code' => 'DM_01',
                'name' => 'S\'intéresse à l\'environnement',
                'description' => 'Curiosité pour le monde qui l\'entoure',
                'domain' => 'Découverte du monde',
                'sort_order' => 1
            ],
            [
                'code' => 'DM_02',
                'name' => 'Pose des questions pertinentes',
                'description' => 'Capacité à questionner son environnement',
                'domain' => 'Découverte du monde',
                'sort_order' => 2
            ],
            [
                'code' => 'DM_03',
                'name' => 'Observe et décrit',
                'description' => 'Capacité à observer et à décrire ce qu\'il voit',
                'domain' => 'Découverte du monde',
                'sort_order' => 3
            ],

            // Domain: Motricité et Psychomotricité
            [
                'code' => 'MP_01',
                'name' => 'Se produit devant les autres',
                'description' => 'Capacité à se présenter devant un public',
                'domain' => 'Motricité et Psychomotricité',
                'sort_order' => 1
            ],
            [
                'code' => 'MP_02',
                'name' => 'Accepte l\'effort',
                'description' => 'Capacité à persévérer dans l\'effort',
                'domain' => 'Motricité et Psychomotricité',
                'sort_order' => 2
            ],
            [
                'code' => 'MP_03',
                'name' => 'Participe à des jeux collectifs',
                'description' => 'Capacité à jouer en équipe',
                'domain' => 'Motricité et Psychomotricité',
                'sort_order' => 3
            ],
            [
                'code' => 'MP_04',
                'name' => 'Coordonne ses gestes',
                'description' => 'Capacité à coordonner ses mouvements',
                'domain' => 'Motricité et Psychomotricité',
                'sort_order' => 4
            ],
        ];

        // Ajouter les timestamps
        $now = now();
        foreach ($competencies as &$competency) {
            $competency['created_at'] = $now;
            $competency['updated_at'] = $now;
            $competency['is_active'] = true;
        }

        // Insérer les compétences
        DB::table('pre_primary_competencies')->insert($competencies);

        $this->command->info('✅ ' . count($competencies) . ' compétences du préprimaire créées avec succès!');
        $this->command->info('📊 Domaines créés:');
        $domains = array_unique(array_column($competencies, 'domain'));
        foreach ($domains as $domain) {
            $count = count(array_filter($competencies, fn($c) => $c['domain'] === $domain));
            $this->command->info("   - {$domain}: {$count} compétences");
        }
    }
}
