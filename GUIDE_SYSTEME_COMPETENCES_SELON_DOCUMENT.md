# 📚 Guide - Système de Gestion des Compétences selon le Document Officiel

## 📋 Vue d'ensemble

Ce document décrit les modifications apportées au système de gestion des compétences pour le faire correspondre exactement au document officiel fourni.

## 🎯 Changements effectués

### 1. Structure des Critères

#### ❌ Avant (Incorrect)
- **4 critères** par compétence (C1, C2, C3, C4)
- Points variables par critère

#### ✅ Après (Correct selon le document)
- **3 critères** par compétence (C1, C2, C3)
- **3 points maximum** par critère
- **9 points maximum** par compétence (3 × 3)

**Fichier modifié:** `database/seeders/CompetencySeeder.php`

```php
// Exemple pour EDM&EAS Compétence 1
$this->createCriteriaForCompetency($edmEasCompetency1, [
    ['code' => 'C1', 'name' => 'Connaissances historiques', 'max_points' => 3],
    ['code' => 'C2', 'name' => 'Connaissances géographiques', 'max_points' => 3],
    ['code' => 'C3', 'name' => 'Citoyenneté', 'max_points' => 3]
]);
```

### 2. Calcul du Niveau de Maîtrise d'une Compétence

#### ❌ Avant (Incorrect)
Basé sur le **pourcentage** des points obtenus :
- Maximale : ≥ 90%
- Minimale : 75-89%
- Partielle : 50-74%
- Non maîtrise : < 50%

#### ✅ Après (Correct selon le document)
Basé sur les **points absolus** obtenus :
- **Maxi (maximale)** : 8 à 9 points
- **Mini (minimale)** : 5 à 7 points
- **Part (partielle)** : 3 à 4 points
- **N M (non maîtrise)** : 0 à 2 points

**Fichier modifié:** `app/Http/Controllers/CompetencyEvaluationController.php`

```php
private function calculateMasteryLevel($totalPoints)
{
    if ($totalPoints >= 8) return 'maximale';
    if ($totalPoints >= 5) return 'minimale';
    if ($totalPoints >= 3) return 'partielle';
    return 'non_maitrise';
}
```

### 3. Calcul de la Maîtrise d'une Matière

#### ✅ Nouveau (selon le document)

**Pour les matières ayant 3 compétences (EDM&EAS) :**
- Maîtrise maximale : 3 compétences réussies sur 3
- Maîtrise minimale : 2 compétences réussies sur 3
- Maîtrise partielle : 1 compétence réussie sur 3
- Non maîtrise : 0 compétence réussie sur 3

**Pour les matières ayant 2 compétences (Français, Mathématiques) :**
- Maîtrise maximale : 2 compétences réussies sur 2
- Maîtrise minimale : 1 compétence réussie sur 2
- Non maîtrise : 0 compétence réussie sur 2

**Note :** Une compétence est considérée comme réussie si elle a au moins une maîtrise minimale (≥ 5 points).

**Méthode ajoutée :**

```php
private function calculateSubjectMastery($studentId, $subjectArea, $palier, $academicYearId, $classId)
{
    // Récupérer toutes les compétences de la matière pour cet élève et ce palier
    $competencyIds = Competency::where('subject_area', $subjectArea)->pluck('id');
    
    $evaluations = StudentCompetencyEvaluation::where('student_id', $studentId)
        ->where('palier', $palier)
        ->where('academic_year_id', $academicYearId)
        ->where('class_id', $classId)
        ->whereIn('competency_id', $competencyIds)
        ->get();
    
    $totalCompetencies = $competencyIds->count();
    $successfulCompetencies = $evaluations->filter(function($eval) {
        return in_array($eval->competency_mastery, ['minimale', 'maximale']);
    })->count();
    
    // Calcul selon le nombre de compétences de la matière
    if ($totalCompetencies == 3) {
        // Pour EDM&EAS (3 compétences)
        if ($successfulCompetencies == 3) return 'maximale';
        if ($successfulCompetencies == 2) return 'minimale';
        if ($successfulCompetencies == 1) return 'partielle';
        return 'non_maitrise';
    } else if ($totalCompetencies == 2) {
        // Pour Français et Mathématiques (2 compétences)
        if ($successfulCompetencies == 2) return 'maximale';
        if ($successfulCompetencies == 1) return 'minimale';
        return 'non_maitrise';
    }
    
    return 'non_maitrise';
}
```

### 4. Calcul de la Maîtrise d'un Palier

#### ✅ Nouveau (selon le document)

**Niveau de maîtrise du palier :**
- Maîtrise maximale : 3 matières réussies sur 3
- Maîtrise minimale : 2 matières réussies sur 3
- Maîtrise partielle : 1 matière réussie sur 3
- Non maîtrise : 0 matière réussie sur 3

**Note :** Une matière est considérée comme réussie si elle a au moins une maîtrise minimale.

**Méthode ajoutée :**

```php
private function calculatePalierMastery($studentId, $palier, $academicYearId, $classId)
{
    // Calculer la maîtrise de chaque matière
    $subjects = ['EDM & EAS', 'Français', 'Mathématiques'];
    $successfulSubjects = 0;
    
    foreach ($subjects as $subject) {
        $subjectMastery = $this->calculateSubjectMastery($studentId, $subject, $palier, $academicYearId, $classId);
        if (in_array($subjectMastery, ['minimale', 'maximale'])) {
            $successfulSubjects++;
        }
    }
    
    // Calcul du niveau de maîtrise du palier
    if ($successfulSubjects == 3) return 'maximale';
    if ($successfulSubjects == 2) return 'minimale';
    if ($successfulSubjects == 1) return 'partielle';
    return 'non_maitrise';
}
```

### 5. Conditions de Passage en Classe Supérieure

#### ✅ Nouveau (selon le document)

**Conditions de passage en classe de 5e année :**

L'élève peut passer si **au moins une** des conditions suivantes est remplie :

1. **Condition 1** : Être en situation de réussite (maîtrise minimale ou maximale) dans les **5 paliers**
2. **Condition 2** : Avoir au moins une maîtrise minimale dans les **3 derniers paliers** (paliers 3, 4, 5)
3. **Condition 3** : Avoir au moins une maîtrise minimale dans les **paliers 4 et 5** ET une maîtrise partielle dans le **palier 3**

**Méthode ajoutée :**

```php
private function checkPassageConditions($studentId, $academicYearId, $classId)
{
    $palierMasteries = [];
    
    for ($palier = 1; $palier <= 5; $palier++) {
        $palierMasteries[$palier] = $this->calculatePalierMastery($studentId, $palier, $academicYearId, $classId);
    }
    
    // Condition 1: Situation de réussite (minimale ou maximale) dans les 5 paliers
    $condition1 = true;
    for ($palier = 1; $palier <= 5; $palier++) {
        if (!in_array($palierMasteries[$palier], ['minimale', 'maximale'])) {
            $condition1 = false;
            break;
        }
    }
    
    // Condition 2: Maîtrise minimale dans les 3 derniers paliers (3, 4, 5)
    $condition2 = in_array($palierMasteries[3], ['minimale', 'maximale']) &&
                  in_array($palierMasteries[4], ['minimale', 'maximale']) &&
                  in_array($palierMasteries[5], ['minimale', 'maximale']);
    
    // Condition 3: Maîtrise minimale dans paliers 4 et 5 + maîtrise partielle au minimum au palier 3
    $condition3 = in_array($palierMasteries[4], ['minimale', 'maximale']) &&
                  in_array($palierMasteries[5], ['minimale', 'maximale']) &&
                  in_array($palierMasteries[3], ['partielle', 'minimale', 'maximale']);
    
    return [
        'can_pass' => $condition1 || $condition2 || $condition3,
        'conditions' => [
            'all_paliers_successful' => $condition1,
            'last_three_paliers_minimal' => $condition2,
            'paliers_4_5_minimal_3_partial' => $condition3
        ],
        'palier_masteries' => $palierMasteries
    ];
}
```

### 6. API pour Vérifier les Conditions de Passage

#### ✅ Nouveau

Une nouvelle route API a été ajoutée pour vérifier les conditions de passage d'un élève :

**Route :** `GET /competency-evaluations/api/student-passage-conditions/{studentId}`

**Réponse JSON :**

```json
{
    "success": true,
    "student": {
        "id": 1,
        "name": "Jean Dupont",
        "class": "CP1-A"
    },
    "passage_conditions": {
        "can_pass": true,
        "conditions": {
            "all_paliers_successful": true,
            "last_three_paliers_minimal": true,
            "paliers_4_5_minimal_3_partial": true
        },
        "palier_masteries": {
            "1": "maximale",
            "2": "minimale",
            "3": "minimale",
            "4": "maximale",
            "5": "maximale"
        }
    }
}
```

**Fichiers modifiés :**
- `app/Http/Controllers/CompetencyEvaluationController.php` : Méthode `checkStudentPassageConditions()`
- `routes/web.php` : Ajout de la route

## 📊 Tableau Récapitulatif

| Aspect | Avant | Après |
|--------|-------|-------|
| Nombre de critères | 4 (C1, C2, C3, C4) | 3 (C1, C2, C3) |
| Points max/critère | Variable | 3 points |
| Points max/compétence | Variable | 9 points |
| Calcul maîtrise compétence | Pourcentage | Points absolus |
| Calcul maîtrise matière | Non calculé | Selon nb compétences réussies |
| Calcul maîtrise palier | Non calculé | Selon nb matières réussies |
| Conditions de passage | Non implémentées | 3 conditions alternatives |

## 🔄 Impact sur les Méthodes Existantes

### Méthodes `store()` et `storeSingleStudent()`

Ces méthodes ont été modifiées pour :
1. Utiliser le nouveau calcul de maîtrise basé sur les points absolus
2. Calculer automatiquement la maîtrise de la matière après chaque évaluation
3. Calculer automatiquement la maîtrise du palier après chaque évaluation

**Changements clés :**

```php
// Calculer le niveau de maîtrise de la compétence (basé sur les points absolus)
$masteryLevel = $this->calculateMasteryLevel($totalPointsObtained);

// Calculer et mettre à jour la maîtrise de chaque matière
foreach ($studentEvaluations->groupBy('competency.subject_area') as $subjectArea => $evaluations) {
    $subjectMastery = $this->calculateSubjectMastery($studentId, $subjectArea, $palier, $academicYearId, $classId);
    
    foreach ($evaluations as $evaluation) {
        $evaluation->update(['subject_mastery' => $subjectMastery]);
    }
}

// Calculer et mettre à jour la maîtrise du palier
$palierMastery = $this->calculatePalierMastery($studentId, $palier, $academicYearId, $classId);
```

## 🧪 Tests Recommandés

1. **Test des critères :**
   - Vérifier que chaque compétence a exactement 3 critères
   - Vérifier que chaque critère vaut 3 points maximum

2. **Test du calcul de maîtrise de compétence :**
   - 9 points → maximale
   - 7 points → minimale
   - 4 points → partielle
   - 2 points → non_maitrise

3. **Test du calcul de maîtrise de matière :**
   - EDM&EAS : 3/3 compétences réussies → maximale
   - Français : 1/2 compétences réussies → minimale
   - Mathématiques : 0/2 compétences réussies → non_maitrise

4. **Test du calcul de maîtrise de palier :**
   - 3/3 matières réussies → maximale
   - 2/3 matières réussies → minimale
   - 1/3 matières réussies → partielle

5. **Test des conditions de passage :**
   - Vérifier les 3 conditions alternatives
   - Tester des cas limites

## 📝 Notes Importantes

1. **Rétrocompatibilité :** Les anciennes données avec 4 critères devront être migrées ou nettoyées.

2. **Base de données :** Exécuter le nouveau seeder pour créer les compétences avec la bonne structure :
   ```bash
   php artisan db:seed --class=CompetencySeeder
   ```

3. **Documentation des matières :** Le système reconnaît exactement 3 matières :
   - EDM & EAS (3 compétences)
   - Français (2 compétences)
   - Mathématiques (2 compétences)

4. **Total des compétences :** 7 compétences au total (3 + 2 + 2)

## 🎓 Référence au Document

Toutes les modifications sont basées sur le document officiel qui spécifie :
- L'identification des disciplines à l'intérieur de chaque compétence
- Les situations de réussite et d'échec
- Les niveaux de maîtrise par matière
- Les conditions de passage en classe supérieure

## ✅ Checklist de Vérification

- [x] Modifier CompetencySeeder pour 3 critères
- [x] Modifier calculateMasteryLevel pour points absolus
- [x] Ajouter calculateSubjectMastery
- [x] Ajouter calculatePalierMastery
- [x] Ajouter checkPassageConditions
- [x] Modifier store() et storeSingleStudent()
- [x] Ajouter API checkStudentPassageConditions
- [x] Ajouter route pour l'API
- [x] Créer documentation complète

## 🚀 Prochaines Étapes

1. Tester le système avec des données réelles
2. Mettre à jour l'interface utilisateur pour afficher les conditions de passage
3. Créer des rapports pour visualiser les maîtrises par palier
4. Implémenter des alertes pour les élèves en difficulté

