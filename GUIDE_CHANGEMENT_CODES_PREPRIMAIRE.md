# Guide : Changement des Codes d'Évaluation Préprimaire

## 📋 Vue d'ensemble

Ce document décrit les changements effectués pour aligner les codes d'évaluation du préprimaire avec ceux du système primaire.

---

## 🔄 Changement des Codes

### Anciens Codes → Nouveaux Codes

| Ancien Code | Ancien Libellé | Nouveau Code | Nouveau Libellé | Signification |
|-------------|----------------|--------------|-----------------|---------------|
| **A** | Acquis | **MAX** | Compétence Acquise | Maîtrise maximale |
| **AR** | À Renforcer | **MIN** | À Renforcer | Maîtrise minimale |
| **AB** | À Bientôt | **PART** | Non Acquise | Maîtrise partielle |
| **NA** | Non Abordé | **NM** | Encore Non Abordée | Non maîtrise |

---

## 📁 Fichiers Modifiés

### 1. Migration : `2025_11_03_090211_create_pre_primary_competency_evaluations_table.php`

**Changements :**
- Enum des colonnes `trimester_1_code`, `trimester_2_code`, `trimester_3_code` :
  - Avant : `['A', 'AR', 'AB', 'NA']`
  - Après : `['MAX', 'MIN', 'PART', 'NM']`
  
- Correction des noms de contraintes trop longs :
  - Clé étrangère vers `pre_primary_competencies` : `pp_comp_eval_comp_id_fk`
  - Index student/year : `pp_eval_student_year_idx`
  - Index class/year : `pp_eval_class_year_idx`
  - Unique constraint : `pp_eval_unique_student_comp_year`

- Retrait temporaire des contraintes de clés étrangères vers les tables non encore créées

### 2. Modèle : `app/Models/PrePrimaryCompetencyEvaluation.php`

**Changements des constantes :**
```php
// Avant
const CODE_ACQUIS = 'A';
const CODE_A_RENFORCER = 'AR';
const CODE_A_BIENTOT = 'AB';
const CODE_NON_ABORDE = 'NA';

// Après
const CODE_MAXIMALE = 'MAX';
const CODE_MINIMALE = 'MIN';
const CODE_PARTIELLE = 'PART';
const CODE_NON_MAITRISE = 'NM';
```

**Changements de la méthode `getCodeLabel()` :**
```php
'MAX' => 'Compétence Acquise'
'MIN' => 'À Renforcer'
'PART' => 'Non Acquise'
'NM' => 'Encore Non Abordée'
```

**Changements de la méthode `isAcquiredForTrimester()` :**
- Vérification changée de `CODE_ACQUIS` à `CODE_MAXIMALE`

### 3. Vue : `resources/views/pre-primary-evaluations/index.blade.php`

**Changements dans la section "Codes d'évaluation" :**
```html
<li><span class="badge bg-success">MAX</span> <strong>Compétence Acquise</strong> - Maîtrise maximale</li>
<li><span class="badge bg-warning text-dark">MIN</span> <strong>À Renforcer</strong> - Maîtrise minimale</li>
<li><span class="badge bg-danger">PART</span> <strong>Non Acquise</strong> - Maîtrise partielle</li>
<li><span class="badge bg-secondary">NM</span> <strong>Encore Non Abordée</strong> - Non maîtrise</li>
```

---

## 🎯 Alignement avec le Système Primaire

Les codes du préprimaire sont maintenant **alignés** avec ceux du système primaire :

| Système | Maximale | Minimale | Partielle | Non Maîtrise |
|---------|----------|----------|-----------|--------------|
| **Primaire** | MAX (8-9 pts) | MIN (5-7 pts) | PART (3-4 pts) | NM (0-2 pts) |
| **Préprimaire** | MAX | MIN | PART | NM |

**Avantages de cet alignement :**
1. ✅ Cohérence entre les deux systèmes
2. ✅ Facilité de compréhension pour les enseignants
3. ✅ Uniformité dans les rapports et statistiques
4. ✅ Simplification de la maintenance du code

---

## 🗄️ Base de Données

### Tables Créées

1. **`pre_primary_competencies`** : 48 compétences sur 10 domaines
2. **`pre_primary_competency_evaluations`** : Évaluations par trimestre (avec codes MAX, MIN, PART, NM)

### Commandes Exécutées

```bash
# Migration des compétences
php artisan migrate --path=database/migrations/2025_11_03_090244_create_pre_primary_competencies_table.php

# Migration des évaluations
php artisan migrate --path=database/migrations/2025_11_03_090211_create_pre_primary_competency_evaluations_table.php

# Seeding des compétences
php artisan db:seed --class=PrePrimaryCompetencySeeder
```

---

## 📊 Domaines de Compétences

Les 48 compétences sont réparties sur 10 domaines :

1. **Compétences transversales, faces d'être** : 8 compétences
2. **Langue orale** : 5 compétences
3. **Production Lecture** : 5 compétences
4. **Graphomotricité Écriture** : 5 compétences
5. **Logico-Mathématiques et Précopto-motrice** : 5 compétences
6. **Espace et temps** : 4 compétences
7. **EPS** : 6 compétences
8. **Engagement Moral** : 3 compétences
9. **Découverte du monde** : 3 compétences
10. **Motricité et Psychomotricité** : 4 compétences

---

## ✅ État de Complétion

- [x] Migration des tables
- [x] Modification du modèle
- [x] Mise à jour de la vue index
- [x] Seeding des compétences
- [x] Documentation
- [ ] Vue de saisie des évaluations (create.blade.php)
- [ ] Vue des bulletins (bulletins.blade.php)
- [ ] Génération PDF des bulletins

---

## 📝 Prochaines Étapes

1. Créer la vue `create.blade.php` pour la saisie des évaluations
2. Créer la vue `bulletins.blade.php` pour l'affichage des bulletins
3. Implémenter la génération PDF avec jsPDF
4. Ajouter les statistiques par classe et par domaine

---

**Date de création** : 3 novembre 2025  
**Dernière mise à jour** : 3 novembre 2025  
**Système** : EGESCO - Gestion d'École

