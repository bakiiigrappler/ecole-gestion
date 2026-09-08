<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Competency;
use App\Models\CompetencyCriteria;

class CompetencySeeder extends Seeder
{
    public function run()
    {
        // Compétences EDM & EAS
        $edmEasCompetency1 = Competency::create([
            'name' => 'Histoire, géographie, citoyenneté',
            'code' => 'COMP_1',
            'description' => 'Maîtrise des connaissances en histoire, géographie et éducation à la citoyenneté',
            'subject_area' => 'EDM & EAS',
            'sort_order' => 1
        ]);

        $edmEasCompetency2 = Competency::create([
            'name' => 'Sciences, Technologie et informatique',
            'code' => 'COMP_2',
            'description' => 'Maîtrise des sciences, de la technologie et de l\'informatique',
            'subject_area' => 'EDM & EAS',
            'sort_order' => 2
        ]);

        $edmEasCompetency3 = Competency::create([
            'name' => 'EPS et art',
            'code' => 'COMP_3',
            'description' => 'Maîtrise de l\'éducation physique et sportive et des arts',
            'subject_area' => 'EDM & EAS',
            'sort_order' => 3
        ]);

        // Compétences Français
        $francaisCompetency1 = Competency::create([
            'name' => 'Compréhension orale et langage',
            'code' => 'COMP_4',
            'description' => 'Maîtrise de la compréhension orale et du langage',
            'subject_area' => 'Français',
            'sort_order' => 1
        ]);

        $francaisCompetency2 = Competency::create([
            'name' => 'Lecture, écriture et production écrite',
            'code' => 'COMP_5',
            'description' => 'Maîtrise de la lecture, de l\'écriture et de la production écrite',
            'subject_area' => 'Français',
            'sort_order' => 2
        ]);

        // Compétences Mathématiques
        $mathCompetency1 = Competency::create([
            'name' => 'Nombres et opérations et Résolution des problèmes',
            'code' => 'COMP_6',
            'description' => 'Maîtrise des nombres, des opérations et de la résolution de problèmes',
            'subject_area' => 'Mathématiques',
            'sort_order' => 1
        ]);

        $mathCompetency2 = Competency::create([
            'name' => 'Géométrie et Mesure',
            'code' => 'COMP_7',
            'description' => 'Maîtrise de la géométrie et des mesures',
            'subject_area' => 'Mathématiques',
            'sort_order' => 2
        ]);

        // Créer les critères pour chaque compétence
        // Selon le document: seulement 3 critères (C1, C2, C3) par compétence
        // Max 9 points par compétence pour permettre la classification Maxi:8-9, Mini:5-7, Part:3-4, NM:0-2
        $this->createCriteriaForCompetency($edmEasCompetency1, [
            ['code' => 'C1', 'name' => 'Connaissances historiques', 'max_points' => 3],
            ['code' => 'C2', 'name' => 'Connaissances géographiques', 'max_points' => 3],
            ['code' => 'C3', 'name' => 'Citoyenneté', 'max_points' => 3]
        ]);

        $this->createCriteriaForCompetency($edmEasCompetency2, [
            ['code' => 'C1', 'name' => 'Connaissances scientifiques', 'max_points' => 3],
            ['code' => 'C2', 'name' => 'Technologie et TIC', 'max_points' => 3],
            ['code' => 'C3', 'name' => 'Expérimentation', 'max_points' => 3]
        ]);

        $this->createCriteriaForCompetency($edmEasCompetency3, [
            ['code' => 'C1', 'name' => 'Éducation physique et sportive', 'max_points' => 3],
            ['code' => 'C2', 'name' => 'Arts plastiques', 'max_points' => 3],
            ['code' => 'C3', 'name' => 'Expression artistique', 'max_points' => 3]
        ]);

        $this->createCriteriaForCompetency($francaisCompetency1, [
            ['code' => 'C1', 'name' => 'Production orale', 'max_points' => 3],
            ['code' => 'C2', 'name' => 'Compréhension orale', 'max_points' => 3],
            ['code' => 'C3', 'name' => 'Expression orale', 'max_points' => 3]
        ]);

        $this->createCriteriaForCompetency($francaisCompetency2, [
            ['code' => 'C1', 'name' => 'Lecture et compréhension', 'max_points' => 3],
            ['code' => 'C2', 'name' => 'Grammaire, conjugaison, orthographe, vocabulaire', 'max_points' => 3],
            ['code' => 'C3', 'name' => 'Production écrite', 'max_points' => 3]
        ]);

        $this->createCriteriaForCompetency($mathCompetency1, [
            ['code' => 'C1', 'name' => 'Nombres et opérations', 'max_points' => 3],
            ['code' => 'C2', 'name' => 'Calcul et techniques', 'max_points' => 3],
            ['code' => 'C3', 'name' => 'Résolution de problèmes', 'max_points' => 3]
        ]);

        $this->createCriteriaForCompetency($mathCompetency2, [
            ['code' => 'C1', 'name' => 'Géométrie', 'max_points' => 3],
            ['code' => 'C2', 'name' => 'Mesures et grandeurs', 'max_points' => 3],
            ['code' => 'C3', 'name' => 'Application pratique', 'max_points' => 3]
        ]);
    }

    private function createCriteriaForCompetency($competency, $criteriaData)
    {
        foreach ($criteriaData as $index => $criteria) {
            CompetencyCriteria::create([
                'competency_id' => $competency->id,
                'code' => $criteria['code'],
                'name' => $criteria['name'],
                'max_points' => $criteria['max_points'],
                'sort_order' => $index + 1
            ]);
        }
    }
}
