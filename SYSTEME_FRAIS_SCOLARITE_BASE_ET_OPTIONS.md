# 💰 Système de Frais - Scolarité de Base + Options

## 🎯 Concept

Le système distingue maintenant **deux types de frais** :

1. **💰 Frais de Scolarité de Base** (Obligatoires)
   - Un seul frais par cycle
   - Montant fixe et obligatoire
   - Inclut : inscription, manuels, matériel de base
   - Va directement dans le champ "Frais totaux"

2. **➕ Frais Optionnels** (Sélectionnables)
   - Services et équipements supplémentaires
   - L'utilisateur choisit ce qu'il veut
   - Exemples : uniforme, transport, cantine, etc.

---

## 📊 Frais de Scolarité de Base par Cycle

| Cycle | Montant | Inclut |
|-------|---------|--------|
| **Préprimaire** | **155,000 FCFA** | Scolarité + Inscription + Matériel pédagogique |
| **Primaire** | **205,000 FCFA** | Scolarité + Inscription + Manuels scolaires |
| **Collège** | **285,000 FCFA** | Scolarité + Inscription + Manuels + Laboratoire |
| **Lycée** | **355,000 FCFA** | Scolarité + Inscription + Manuels + Labo + Info |

**Caractéristiques** :
- ✅ **Obligatoire** : Ne peut pas être décoché
- ✅ **Fixe** : Montant unique par cycle
- ✅ **Tout inclus** : Regroupe tous les frais de base
- ✅ **Affiché séparément** : Dans un champ dédié

---

## ➕ Frais Optionnels (6 frais)

| Frais | Montant | Fréquence | Description |
|-------|---------|-----------|-------------|
| Uniforme complet | 25,000 F | Annuel | Tenue complète |
| Tenue de sport | 15,000 F | Annuel | Survêtement |
| Transport scolaire | 30,000 F | **Mensuel** | Abonnement transport |
| Cantine scolaire | 25,000 F | **Mensuel** | Repas du midi |
| Assurance scolaire | 10,000 F | Annuel | Assurance accidents |
| Carte d'étudiant | 2,000 F | Annuel | Carte avec photo |

**Tous optionnels** : L'utilisateur choisit ce qu'il veut

---

## 🖥️ Interface dans le Formulaire

### **1. Frais de Scolarité de Base**

```
┌──────────────────────────────────────────────────────┐
│ 🎓 Frais de scolarité de base * [Obligatoire]       │
├──────────────────────────────────────────────────────┤
│ [    205,000    ] FCFA                               │
│                                                      │
│ Frais de scolarité obligatoires pour le cycle       │
│ primaire (inclut inscription et manuels scolaires)   │
└──────────────────────────────────────────────────────┘
```

**Caractéristiques** :
- 🔵 Fond bleu sur le label "FCFA"
- 🔒 Champ en lecture seule
- 📝 Description détaillée en dessous

### **2. Tableau des Frais Optionnels**

```
┌──────────────────────────────────────────────────────┐
│ ✅ Frais optionnels disponibles                      │
├──────────────────────────────────────────────────────┤
│ Frais                        │ Montant    │ ☑ Tout  │
├──────────────────────────────┼────────────┼─────────┤
│ 🎓 Frais optionnels du cycle primaire               │
├──────────────────────────────┼────────────┼─────────┤
│ (Aucun pour le moment)                              │
├──────────────────────────────┼────────────┼─────────┤
│ ⚙️ Frais généraux optionnels (tous cycles)          │
├──────────────────────────────┼────────────┼─────────┤
│ Uniforme complet             │  25,000 F  │ ☑       │
│ Tenue de sport               │  15,000 F  │ ☑       │
│ Transport [Mensuel]          │  30,000 F  │ ☑       │
│ Cantine [Mensuel]            │  25,000 F  │ ☑       │
│ Assurance scolaire           │  10,000 F  │ ☑       │
│ Carte d'étudiant             │   2,000 F  │ ☑       │
├──────────────────────────────┼────────────┼─────────┤
│ Total sélectionné            │ 107,000 F  │         │
└──────────────────────────────────────────────────────┘
```

### **3. Frais Optionnels Sélectionnés**

```
┌──────────────────────────────────────────────────────┐
│ ➕ Frais optionnels sélectionnés                     │
├──────────────────────────────────────────────────────┤
│ [    107,000    ] FCFA                               │
│                                                      │
│ Total des frais optionnels cochés                    │
└──────────────────────────────────────────────────────┘
```

### **4. Total à Payer**

```
┌──────────────────────────────────────────────────────┐
│ 🧮 Total à payer *                                   │
├──────────────────────────────────────────────────────┤
│ [    312,000    ] FCFA                               │
│                                                      │
│ Total = Scolarité de base + Frais optionnels        │
│         (205,000 + 107,000)                          │
└──────────────────────────────────────────────────────┘
```

**Caractéristiques** :
- 🟢 Fond vert sur le label "FCFA"
- 📊 Texte en gras
- 📝 Formule de calcul affichée

---

## 🔢 Exemples de Calculs

### **Exemple 1 : Primaire - Minimum**

```
Scolarité de base (Primaire)     : 205,000 F
Frais optionnels                 :       0 F
─────────────────────────────────────────────
TOTAL À PAYER                    : 205,000 F
```

### **Exemple 2 : Primaire - Avec Uniforme**

```
Scolarité de base (Primaire)     : 205,000 F
Frais optionnels :
  • Uniforme complet             :  25,000 F
  • Tenue de sport               :  15,000 F
  • Carte d'étudiant             :   2,000 F
─────────────────────────────────────────────
TOTAL À PAYER                    : 247,000 F
```

### **Exemple 3 : Collège - Avec Services**

```
Scolarité de base (Collège)      : 285,000 F
Frais optionnels :
  • Uniforme complet             :  25,000 F
  • Tenue de sport               :  15,000 F
  • Transport (30k × 9 mois)     : 270,000 F
  • Cantine (25k × 9 mois)       : 225,000 F
  • Assurance scolaire           :  10,000 F
  • Carte d'étudiant             :   2,000 F
─────────────────────────────────────────────
TOTAL À PAYER                    : 832,000 F
```

### **Exemple 4 : Lycée - Complet**

```
Scolarité de base (Lycée)        : 355,000 F
Frais optionnels :
  • Tous les frais généraux      : 107,000 F
─────────────────────────────────────────────
TOTAL À PAYER                    : 462,000 F
```

---

## 🎨 Design et UX

### **Hiérarchie Visuelle**

```
┌─────────────────────────────────────────────┐
│ 1️⃣ SCOLARITÉ DE BASE                       │ ← Bleu, Grand
│    (Obligatoire, non modifiable)            │
├─────────────────────────────────────────────┤
│ 2️⃣ FRAIS OPTIONNELS                        │ ← Normal
│    (Tableau avec checkboxes)                │
├─────────────────────────────────────────────┤
│ 3️⃣ TOTAL OPTIONNELS                        │ ← Info
│    (Somme des cochés)                       │
├─────────────────────────────────────────────┤
│ 4️⃣ TOTAL À PAYER                           │ ← Vert, Grand, Gras
│    (Base + Optionnels)                      │
└─────────────────────────────────────────────┘
```

### **Codes Couleurs**

| Élément | Couleur | Signification |
|---------|---------|---------------|
| Scolarité de base | Bleu (`bg-primary`) | Frais obligatoire principal |
| Total à payer | Vert (`bg-success`) | Montant final |
| Badge "Obligatoire" | Rouge (`bg-danger`) | Ne peut pas être décoché |
| Badge "Mensuel" | Bleu (`bg-info`) | Frais mensuel |
| En-tête cycle | Gris (`table-secondary`) | Section frais du cycle |
| En-tête général | Bleu clair (`table-info`) | Section frais généraux |

---

## 📋 Workflow Complet

### **Étape 1 : Sélection du Niveau**
```
Utilisateur sélectionne : Primaire → CP
```

### **Étape 2 : Chargement API**
```
GET /api/level-fees?level_id=X&academic_year_id=Y
```

### **Étape 3 : Affichage**
```
Scolarité de base : 205,000 F (affiché automatiquement)
Frais optionnels  : Tableau avec 6 frais (tous cochés par défaut)
Total optionnels  : 107,000 F
Total à payer     : 312,000 F
```

### **Étape 4 : Personnalisation**
```
Utilisateur décoche "Transport" et "Cantine"
→ Total optionnels : 52,000 F
→ Total à payer    : 257,000 F
```

### **Étape 5 : Paiement**
```
Utilisateur clique "Payer le montant total"
→ Montant payé : 257,000 F
→ Reste à payer : 0 F
```

### **Étape 6 : Soumission**
```
Formulaire envoyé avec :
- total_fees = 257,000 F
- amount_paid = 257,000 F
- balance_due = 0 F
```

---

## 🔧 Structure Technique

### **Base de Données**

```sql
-- Frais de scolarité de base
level_id = NULL
cycle = 'primaire'
is_general = false
is_base_tuition = true  ← NOUVEAU
amount = 205000

-- Frais optionnel général
level_id = NULL
cycle = NULL
is_general = true
is_base_tuition = false
amount = 25000
```

### **Réponse API**

```json
{
  "base_tuition": {
    "id": 2,
    "name": "Frais de scolarité - Primaire",
    "amount": 205000,
    "description": "Frais de scolarité de base...",
    "is_base_tuition": true
  },
  "base_tuition_amount": 205000,
  "optional_fees": [...],
  "cycle_fees": [],
  "general_fees": [...],
  "optional_total": 107000,
  "grand_total": 312000,
  "cycle": "primaire"
}
```

---

## ✅ Avantages du Nouveau Système

### **Clarté**
- ✅ **Séparation claire** entre obligatoire et optionnel
- ✅ **Frais de base** bien identifiés
- ✅ **Choix transparent** pour les options

### **Flexibilité**
- ✅ **Familles modestes** : Paient uniquement la scolarité de base
- ✅ **Familles moyennes** : Ajoutent quelques options (uniforme, carte)
- ✅ **Familles aisées** : Prennent tous les services

### **Gestion**
- ✅ **Un seul montant de base** par cycle à gérer
- ✅ **Options communes** à tous les cycles
- ✅ **Modification facile** des montants

---

## 📊 Tableau Récapitulatif

### **Montants Minimums (Scolarité de Base Uniquement)**

| Cycle | Scolarité de Base |
|-------|-------------------|
| Préprimaire | 155,000 FCFA |
| Primaire | 205,000 FCFA |
| Collège | 285,000 FCFA |
| Lycée | 355,000 FCFA |

### **Montants avec Toutes les Options**

| Cycle | Base | + Options Annuelles | + Services Mensuels (9 mois) | Total Maximum |
|-------|------|---------------------|------------------------------|---------------|
| Préprimaire | 155,000 F | + 52,000 F | + 495,000 F | 702,000 F |
| Primaire | 205,000 F | + 52,000 F | + 495,000 F | 752,000 F |
| Collège | 285,000 F | + 52,000 F | + 495,000 F | 832,000 F |
| Lycée | 355,000 F | + 52,000 F | + 495,000 F | 902,000 F |

**Options annuelles** : Uniforme (25k) + Tenue sport (15k) + Assurance (10k) + Carte (2k) = 52,000 F  
**Services mensuels** : Transport (30k) + Cantine (25k) = 55,000 F/mois × 9 = 495,000 F

---

## 🎯 Cas d'Usage Réels

### **Cas 1 : Famille Modeste**
```
Scolarité de base uniquement
→ Préprimaire : 155,000 F
→ Primaire    : 205,000 F
→ Collège     : 285,000 F
→ Lycée       : 355,000 F
```

### **Cas 2 : Famille Moyenne**
```
Scolarité de base + Uniforme + Carte
→ Préprimaire : 155,000 + 27,000 = 182,000 F
→ Primaire    : 205,000 + 27,000 = 232,000 F
→ Collège     : 285,000 + 27,000 = 312,000 F
→ Lycée       : 355,000 + 27,000 = 382,000 F
```

### **Cas 3 : Famille Aisée avec Services**
```
Scolarité de base + Toutes options + Services mensuels
→ Préprimaire : 155,000 + 547,000 = 702,000 F
→ Primaire    : 205,000 + 547,000 = 752,000 F
→ Collège     : 285,000 + 547,000 = 832,000 F
→ Lycée       : 355,000 + 547,000 = 902,000 F
```

---

## 🧪 Tests à Effectuer

### **Test 1 : Affichage Scolarité de Base**
1. Sélectionner "Primaire" → "CP"
2. Vérifier que le champ "Scolarité de base" affiche **205,000 F**
3. Vérifier que le champ est en **lecture seule**
4. Vérifier la description sous le champ

### **Test 2 : Frais Optionnels**
1. Vérifier que 6 frais optionnels s'affichent
2. Vérifier qu'ils sont tous **cochés par défaut**
3. Vérifier qu'on peut les **décocher**
4. Vérifier les badges "Mensuel" sur Transport et Cantine

### **Test 3 : Calculs**
1. Total optionnels initial : **107,000 F**
2. Total à payer initial : **312,000 F** (205,000 + 107,000)
3. Décocher "Transport" : Total → **282,000 F**
4. Décocher "Cantine" : Total → **257,000 F**
5. Décocher tout : Total → **205,000 F** (scolarité de base uniquement)

### **Test 4 : Tous les Cycles**
- [ ] Préprimaire → 155,000 F de base
- [ ] Primaire → 205,000 F de base
- [ ] Collège → 285,000 F de base
- [ ] Lycée → 355,000 F de base

---

## 📈 Comparaison Avant/Après

### **Avant** ❌
```
Frais totaux : [_______] FCFA (saisie manuelle)
```
- Pas de distinction base/options
- Tout mélangé
- Risque d'erreurs

### **Après** ✅
```
Scolarité de base : 205,000 F (automatique)
Frais optionnels  : 107,000 F (sélectionnables)
─────────────────────────────────
TOTAL À PAYER     : 312,000 F (calculé)
```
- Séparation claire
- Transparence totale
- Calculs automatiques

---

## 🗂️ Fichiers Modifiés

1. **Migration** : `2025_10_08_151315_add_is_base_tuition_to_level_fees_table.php`
   - Ajout du champ `is_base_tuition`

2. **Seeder** : `database/seeders/SchoolFeesSeeder.php`
   - 4 frais de scolarité de base (un par cycle)
   - 6 frais généraux optionnels

3. **API** : `routes/api.php`
   - Séparation `base_tuition` / `optional_fees`
   - Calculs distincts

4. **Vue** : `resources/views/enrollments/create.blade.php`
   - 3 champs : Base, Optionnels, Total
   - JavaScript mis à jour

---

## 📊 Statistiques Finales

**Total des frais** : **10 frais**
- 💰 Frais de scolarité de base : **4** (un par cycle)
- ➕ Frais généraux optionnels : **6**

**Montants de base** :
- Préprimaire : 155,000 F
- Primaire : 205,000 F
- Collège : 285,000 F
- Lycée : 355,000 F

**Commande** :
```bash
php artisan db:seed --class=SchoolFeesSeeder
```

**Le système est maintenant clair, professionnel et flexible ! 🎉**

---

**Date** : 8 octobre 2025  
**Version** : 2.0  
**Statut** : ✅ Production Ready
