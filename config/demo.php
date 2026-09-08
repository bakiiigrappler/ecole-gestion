<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Comptes de démonstration
    |--------------------------------------------------------------------------
    |
    | Source unique des comptes de test : le seeder les crée, la page de
    | connexion les propose en accès rapide. Une seule liste, donc aucun risque
    | de voir les deux diverger.
    |
    */

    'comptes' => [
        [
            'libelle' => 'Super administrateur',
            'email' => 'superadmin@ecole.com',
            'mot_de_passe' => 'superadmin123',
            'role' => 'superadmin',
            'matricule' => 'SUPER001',
            'nom' => 'Super Administrateur',
        ],
        [
            'libelle' => 'Administrateur',
            'email' => 'admin@ecole.com',
            'mot_de_passe' => 'admin123',
            'role' => 'admin',
            'matricule' => 'ADMIN001',
            'nom' => 'Administrateur',
        ],
        [
            'libelle' => 'Secrétariat',
            'email' => 'secretariat@ecole.com',
            'mot_de_passe' => 'secretariat123',
            'role' => 'secretary',
            'matricule' => 'SECR001',
            'nom' => 'Secrétariat',
        ],
        [
            'libelle' => 'Enseignant',
            'email' => 'enseignant@ecole.com',
            'mot_de_passe' => 'enseignant123',
            'role' => 'teacher',
            'matricule' => 'ENS001',
            'nom' => 'Enseignant',
        ],
        [
            'libelle' => 'Parent d’élève',
            'email' => 'parent@ecole.com',
            'mot_de_passe' => 'parent123',
            'role' => 'parent',
            'matricule' => 'PAR001',
            'nom' => 'Parent d’élève',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Accès rapide sur la page de connexion
    |--------------------------------------------------------------------------
    |
    | Affiche un bouton par compte, qui pré-remplit le formulaire. Confort de
    | démonstration : coupé en production, où publier des mots de passe sur la
    | page de connexion serait une faille.
    |
    */

    'acces_rapide' => env('DEMO_ACCES_RAPIDE', env('APP_ENV') !== 'production'),

];
