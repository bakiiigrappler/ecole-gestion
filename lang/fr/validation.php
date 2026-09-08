<?php

/*
|--------------------------------------------------------------------------
| Messages de validation
|--------------------------------------------------------------------------
|
| L'application est en français (APP_LOCALE=fr) et Laravel ne fournit que les
| messages anglais : sans ce fichier, les formulaires affichent les clés brutes
| (« validation.required »). Les règles couvertes sont celles réellement
| utilisées par les contrôleurs, plus les plus courantes.
|
*/

return [

    'accepted' => 'Le champ :attribute doit être accepté.',
    'active_url' => 'Le champ :attribute n’est pas une URL valide.',
    'after' => 'Le champ :attribute doit être une date postérieure au :date.',
    'after_or_equal' => 'Le champ :attribute doit être une date postérieure ou égale au :date.',
    'alpha' => 'Le champ :attribute ne peut contenir que des lettres.',
    'alpha_dash' => 'Le champ :attribute ne peut contenir que des lettres, chiffres, tirets et underscores.',
    'alpha_num' => 'Le champ :attribute ne peut contenir que des lettres et des chiffres.',
    'array' => 'Le champ :attribute doit être une liste.',
    'before' => 'Le champ :attribute doit être une date antérieure au :date.',
    'before_or_equal' => 'Le champ :attribute doit être une date antérieure ou égale au :date.',

    'between' => [
        'array' => 'Le champ :attribute doit comporter entre :min et :max éléments.',
        'file' => 'Le fichier :attribute doit peser entre :min et :max kilo-octets.',
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
        'string' => 'Le champ :attribute doit comporter entre :min et :max caractères.',
    ],

    'boolean' => 'Le champ :attribute doit valoir vrai ou faux.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'date' => 'Le champ :attribute n’est pas une date valide.',
    'date_equals' => 'Le champ :attribute doit être une date égale au :date.',
    'date_format' => 'Le champ :attribute ne correspond pas au format :format.',
    'declined' => 'Le champ :attribute doit être refusé.',
    'different' => 'Les champs :attribute et :other doivent être différents.',
    'digits' => 'Le champ :attribute doit comporter :digits chiffres.',
    'digits_between' => 'Le champ :attribute doit comporter entre :min et :max chiffres.',
    'dimensions' => 'La taille de l’image :attribute n’est pas conforme.',
    'distinct' => 'Le champ :attribute contient une valeur en double.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'ends_with' => 'Le champ :attribute doit se terminer par l’un des éléments suivants : :values.',
    'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'filled' => 'Le champ :attribute doit avoir une valeur.',

    'gt' => [
        'array' => 'Le champ :attribute doit comporter plus de :value éléments.',
        'file' => 'Le fichier :attribute doit peser plus de :value kilo-octets.',
        'numeric' => 'Le champ :attribute doit être supérieur à :value.',
        'string' => 'Le champ :attribute doit comporter plus de :value caractères.',
    ],

    'gte' => [
        'array' => 'Le champ :attribute doit comporter au moins :value éléments.',
        'file' => 'Le fichier :attribute doit peser au moins :value kilo-octets.',
        'numeric' => 'Le champ :attribute doit être supérieur ou égal à :value.',
        'string' => 'Le champ :attribute doit comporter au moins :value caractères.',
    ],

    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'in_array' => 'Le champ :attribute n’existe pas dans :other.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'ip' => 'Le champ :attribute doit être une adresse IP valide.',
    'ipv4' => 'Le champ :attribute doit être une adresse IPv4 valide.',
    'ipv6' => 'Le champ :attribute doit être une adresse IPv6 valide.',
    'json' => 'Le champ :attribute doit être un document JSON valide.',

    'lt' => [
        'array' => 'Le champ :attribute doit comporter moins de :value éléments.',
        'file' => 'Le fichier :attribute doit peser moins de :value kilo-octets.',
        'numeric' => 'Le champ :attribute doit être inférieur à :value.',
        'string' => 'Le champ :attribute doit comporter moins de :value caractères.',
    ],

    'lte' => [
        'array' => 'Le champ :attribute doit comporter au plus :value éléments.',
        'file' => 'Le fichier :attribute doit peser au plus :value kilo-octets.',
        'numeric' => 'Le champ :attribute doit être inférieur ou égal à :value.',
        'string' => 'Le champ :attribute doit comporter au plus :value caractères.',
    ],

    'max' => [
        'array' => 'Le champ :attribute ne peut comporter plus de :max éléments.',
        'file' => 'Le fichier :attribute ne peut pas dépasser :max kilo-octets.',
        'numeric' => 'Le champ :attribute ne peut pas être supérieur à :max.',
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],

    'mimes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'mimetypes' => 'Le champ :attribute doit être un fichier de type : :values.',

    'min' => [
        'array' => 'Le champ :attribute doit comporter au moins :min éléments.',
        'file' => 'Le fichier :attribute doit peser au moins :min kilo-octets.',
        'numeric' => 'Le champ :attribute doit valoir au moins :min.',
        'string' => 'Le champ :attribute doit comporter au moins :min caractères.',
    ],

    'not_in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'not_regex' => 'Le format du champ :attribute est invalide.',
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'password' => 'Le mot de passe est incorrect.',
    'present' => 'Le champ :attribute doit être présent.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'required_if' => 'Le champ :attribute est obligatoire lorsque :other vaut :value.',
    'required_unless' => 'Le champ :attribute est obligatoire sauf si :other vaut :values.',
    'required_with' => 'Le champ :attribute est obligatoire lorsque :values est renseigné.',
    'required_with_all' => 'Le champ :attribute est obligatoire lorsque :values sont renseignés.',
    'required_without' => 'Le champ :attribute est obligatoire lorsque :values n’est pas renseigné.',
    'required_without_all' => 'Le champ :attribute est obligatoire lorsqu’aucun de :values n’est renseigné.',
    'same' => 'Les champs :attribute et :other doivent être identiques.',

    'size' => [
        'array' => 'Le champ :attribute doit comporter :size éléments.',
        'file' => 'Le fichier :attribute doit peser :size kilo-octets.',
        'numeric' => 'Le champ :attribute doit valoir :size.',
        'string' => 'Le champ :attribute doit comporter :size caractères.',
    ],

    'starts_with' => 'Le champ :attribute doit commencer par l’un des éléments suivants : :values.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'timezone' => 'Le champ :attribute doit être un fuseau horaire valide.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',
    'uploaded' => 'Le téléversement du fichier :attribute a échoué.',
    'url' => 'Le champ :attribute doit être une URL valide.',
    'uuid' => 'Le champ :attribute doit être un UUID valide.',

    /*
    |--------------------------------------------------------------------------
    | Noms lisibles des champs
    |--------------------------------------------------------------------------
    |
    | Sans cette table, les messages affichent le nom technique de la colonne
    | (« Le champ first_name est obligatoire »).
    |
    */

    'attributes' => [
        'academic_year_id' => 'année scolaire',
        'address' => 'adresse',
        'amount' => 'montant',
        'capacity' => 'capacité',
        'class_id' => 'classe',
        'coefficient' => 'coefficient',
        'current_password' => 'mot de passe actuel',
        'cycle' => 'cycle',
        'date_of_birth' => 'date de naissance',
        'description' => 'description',
        'diploma_file' => 'diplôme',
        'email' => 'adresse e-mail',
        'emergency_contact' => 'contact d’urgence',
        'employee_id' => 'identifiant employé',
        'enrollment_date' => 'date d’inscription',
        'first_name' => 'prénom',
        'gender' => 'sexe',
        'hire_date' => 'date d’embauche',
        'last_name' => 'nom',
        'level_id' => 'niveau',
        'matricule' => 'matricule',
        'medical_conditions' => 'conditions médicales',
        'name' => 'nom',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'payment_method' => 'moyen de paiement',
        'payment_status' => 'statut de paiement',
        'phone' => 'téléphone',
        'photo' => 'photo',
        'place_of_birth' => 'lieu de naissance',
        'qualification' => 'qualification',
        'relationship' => 'lien de parenté',
        'role' => 'rôle',
        'salary' => 'salaire',
        'school_name' => 'nom de l’établissement',
        'score' => 'note',
        'specialization' => 'spécialité',
        'status' => 'statut',
        'student_id' => 'élève',
        'subject_id' => 'matière',
        'teacher_id' => 'enseignant',
        'term' => 'trimestre',
        'workplace' => 'lieu de travail',
    ],

];
