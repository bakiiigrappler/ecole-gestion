# 💰 Guide Complet - Système de Frais par Cycle

## 🎯 Vue d'Ensemble

Ce guide documente le système complet de gestion des frais scolaires avec deux types de frais :
1. **Frais par cycle** : Spécifiques à chaque cycle (préprimaire, primaire, collège, lycée)
2. **Frais généraux** : Applicables à tous les cycles (uniforme, transport, cantine, etc.)

---

## 📊 Structure des Données

### **Table `level_fees` - Améliorée**

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | ID unique |
| `level_id` | bigint (nullable) | ID du niveau (NULL pour frais généraux) |
| `cycle` | enum (nullable) | Cycle (preprimaire, primaire, college, lycee) |
| `is_general` | boolean | Frais général (non lié au cycle) ? |
| `academic_year_id` | bigint | ID de l'année scolaire |
| `fee_type` | enum | Type de frais |
| `name` | string | Nom du frais |
| `description` | text | Description |
| `amount` | decimal | Montant |
| `frequency` | enum | Fréquence (monthly, quarterly, yearly, one_time) |
| `due_date` | date | Date d'échéance (optionnel) |
| `is_mandatory` | boolean | Frais obligatoire ? |
| `is_active` | boolean | Frais actif ? |
| `sort_order` | integer | Ordre d'affichage |

### **Nouveaux Champs**

| Champ | Valeur | Usage |
|-------|--------|-------|
| `cycle` | preprimaire, primaire, college, lycee | Pour les frais spécifiques à un cycle |
| `is_general` | true/false | Pour les frais applicables à tous les cycles |
| `level_id` | NULL | Pour les frais par cycle et généraux |

---

## 💳 Frais Créés par le Seeder

### **1. Frais du Cycle Préprimaire** 🧸

| Frais | Montant | Type | Obligatoire |
|-------|---------|------|-------------|
| Frais de scolarité - Préprimaire | 120,000 FCFA | Scolarité | ✅ Oui |
| Frais d'inscription - Préprimaire | 20,000 FCFA | Inscription | ✅ Oui |
| Matériel pédagogique - Préprimaire | 15,000 FCFA | Livres | ✅ Oui |

**Total obligatoire** : 155,000 FCFA

---

### **2. Frais du Cycle Primaire** 📚

| Frais | Montant | Type | Obligatoire |
|-------|---------|------|-------------|
| Frais de scolarité - Primaire | 150,000 FCFA | Scolarité | ✅ Oui |
| Frais d'inscription - Primaire | 25,000 FCFA | Inscription | ✅ Oui |
| Manuels scolaires - Primaire | 30,000 FCFA | Livres | ✅ Oui |
| Activités sportives - Primaire | 10,000 FCFA | Activités | ❌ Non |

**Total obligatoire** : 205,000 FCFA  
**Total avec optionnels** : 215,000 FCFA

---

### **3. Frais du Cycle Collège** 🎓

| Frais | Montant | Type | Obligatoire |
|-------|---------|------|-------------|
| Frais de scolarité - Collège | 200,000 FCFA | Scolarité | ✅ Oui |
| Frais d'inscription - Collège | 30,000 FCFA | Inscription | ✅ Oui |
| Manuels scolaires - Collège | 40,000 FCFA | Livres | ✅ Oui |
| Laboratoire - Collège | 15,000 FCFA | Activités | ✅ Oui |
| Activités parascolaires - Collège | 12,000 FCFA | Activités | ❌ Non |

**Total obligatoire** : 285,000 FCFA  
**Total avec optionnels** : 297,000 FCFA

---

### **4. Frais du Cycle Lycée** 🎓🎓

| Frais | Montant | Type | Obligatoire |
|-------|---------|------|-------------|
| Frais de scolarité - Lycée | 250,000 FCFA | Scolarité | ✅ Oui |
| Frais d'inscription - Lycée | 35,000 FCFA | Inscription | ✅ Oui |
| Manuels scolaires - Lycée | 50,000 FCFA | Livres | ✅ Oui |
| Laboratoire et informatique - Lycée | 20,000 FCFA | Activités | ✅ Oui |
| Préparation aux examens - Lycée | 25,000 FCFA | Activités | ❌ Non |

**Total obligatoire** : 355,000 FCFA  
**Total avec optionnels** : 380,000 FCFA

---

### **5. Frais Généraux (Tous Cycles)** 🌐

| Frais | Montant | Type | Obligatoire | Fréquence |
|-------|---------|------|-------------|-----------|
| Uniforme complet | 25,000 FCFA | Uniforme | ❌ Non | Annuel |
| Tenue de sport | 15,000 FCFA | Uniforme | ❌ Non | Annuel |
| Transport scolaire | 30,000 FCFA | Transport | ❌ Non | **Mensuel** |
| Cantine scolaire | 25,000 FCFA | Cantine | ❌ Non | **Mensuel** |
| Assurance scolaire | 10,000 FCFA | Autre | ✅ Oui | Annuel |
| Carte d'étudiant | 2,000 FCFA | Autre | ✅ Oui | Annuel |

**Total obligatoire** : 12,000 FCFA  
**Total avec optionnels** : 107,000 FCFA  
**Total avec optionnels mensuels (9 mois)** : 602,000 FCFA

---

## 🔄 Fonctionnement du Système

### **Lors de la Sélection d'un Niveau**

1. L'utilisateur sélectionne un cycle (ex: Primaire)
2. L'utilisateur sélectionne un niveau (ex: CP)
3. → **Appel API** : `/api/level-fees?level_id={id}&academic_year_id={id}`
4. → Le système récupère :
   - ✅ **Frais du cycle primaire** (4 frais)
   - ✅ **Frais généraux** (6 frais)
5. → **Affichage** dans le tableau avec 2 sections

### **Affichage dans le Formulaire**

```
┌─────────────────────────────────────────────────────────────┐
│ ✅ Frais pour ce niveau                                     │
├─────────────────────────────────────────────────────────────┤
│ Frais                              │ Montant    │ ☑ Tout   │
├────────────────────────────────────┼────────────┼──────────┤
│ 🎓 Frais du cycle primaire                                 │
├────────────────────────────────────┼────────────┼──────────┤
│ Frais de scolarité - Primaire      │ 150,000 F  │ ☑ (bloqué)│
│ [Obligatoire]                      │            │          │
├────────────────────────────────────┼────────────┼──────────┤
│ Frais d'inscription - Primaire     │  25,000 F  │ ☑ (bloqué)│
│ [Obligatoire]                      │            │          │
├────────────────────────────────────┼────────────┼──────────┤
│ Manuels scolaires - Primaire       │  30,000 F  │ ☑ (bloqué)│
│ [Obligatoire]                      │            │          │
├────────────────────────────────────┼────────────┼──────────┤
│ Activités sportives - Primaire     │  10,000 F  │ ☑        │
├────────────────────────────────────┼────────────┼──────────┤
│ ⚙️ Frais généraux (tous cycles)                            │
├────────────────────────────────────┼────────────┼──────────┤
│ Uniforme complet                   │  25,000 F  │ ☑        │
├────────────────────────────────────┼────────────┼──────────┤
│ Tenue de sport                     │  15,000 F  │ ☑        │
├────────────────────────────────────┼────────────┼──────────┤
│ Transport scolaire [Mensuel]       │  30,000 F  │ ☑        │
├────────────────────────────────────┼────────────┼──────────┤
│ Cantine scolaire [Mensuel]         │  25,000 F  │ ☑        │
├────────────────────────────────────┼────────────┼──────────┤
│ Assurance scolaire                 │  10,000 F  │ ☑ (bloqué)│
│ [Obligatoire]                      │            │          │
├────────────────────────────────────┼────────────┼──────────┤
│ Carte d'étudiant                   │   2,000 F  │ ☑ (bloqué)│
│ [Obligatoire]                      │            │          │
├────────────────────────────────────┼────────────┼──────────┤
│ Total sélectionné                  │ 322,000 F  │          │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎨 Badges et Indicateurs

### **Badges de Type**

| Badge | Couleur | Signification |
|-------|---------|---------------|
| **Obligatoire** | Rouge (`bg-danger`) | Frais obligatoire, ne peut pas être décoché |
| **Mensuel** | Bleu (`bg-info`) | Frais à payer chaque mois |

### **En-têtes de Section**

| Section | Couleur | Icône |
|---------|---------|-------|
| **Frais du cycle** | Gris (`table-secondary`) | 🎓 mortarboard |
| **Frais généraux** | Bleu clair (`table-info`) | ⚙️ gear |

---

## 📋 Exemples de Calculs

### **Exemple 1 : Inscription Primaire - Paiement Complet**

**Frais sélectionnés** :
- ✅ Frais de scolarité - Primaire : 150,000 F (obligatoire)
- ✅ Frais d'inscription - Primaire : 25,000 F (obligatoire)
- ✅ Manuels scolaires - Primaire : 30,000 F (obligatoire)
- ✅ Activités sportives - Primaire : 10,000 F (optionnel)
- ✅ Uniforme complet : 25,000 F (optionnel)
- ✅ Assurance scolaire : 10,000 F (obligatoire)
- ✅ Carte d'étudiant : 2,000 F (obligatoire)
- ❌ Transport scolaire : 30,000 F (non sélectionné)
- ❌ Cantine scolaire : 25,000 F (non sélectionné)

**Total** : 252,000 FCFA  
**Montant payé** : 252,000 FCFA  
**Reste à payer** : 0 FCFA

---

### **Exemple 2 : Inscription Collège - Paiement Partiel**

**Frais sélectionnés** :
- ✅ Tous les frais obligatoires du collège : 285,000 F
- ✅ Activités parascolaires : 12,000 F
- ✅ Assurance scolaire : 10,000 F
- ✅ Carte d'étudiant : 2,000 F
- ❌ Uniforme (famille fournit)
- ❌ Transport (parents déposent)
- ❌ Cantine (élève rentre déjeuner)

**Total** : 309,000 FCFA  
**Montant payé** : 150,000 FCFA  
**Reste à payer** : 159,000 FCFA

---

### **Exemple 3 : Inscription Lycée - Avec Services Mensuels**

**Frais sélectionnés** :
- ✅ Tous les frais obligatoires du lycée : 355,000 F
- ✅ Préparation aux examens : 25,000 F
- ✅ Uniforme complet : 25,000 F
- ✅ Tenue de sport : 15,000 F
- ✅ Transport scolaire (mensuel) : 30,000 F × 9 mois = 270,000 F
- ✅ Cantine scolaire (mensuel) : 25,000 F × 9 mois = 225,000 F
- ✅ Assurance scolaire : 10,000 F
- ✅ Carte d'étudiant : 2,000 F

**Total annuel** : 927,000 FCFA  
**Montant payé** : 500,000 FCFA  
**Reste à payer** : 427,000 FCFA

---

## 🔧 Modifications Techniques

### **1. Migration**

**Fichier** : `2025_10_08_144757_add_cycle_and_general_fees_support_to_level_fees_table.php`

**Changements** :
```php
// Rendre level_id nullable pour permettre les frais généraux
$table->foreignId('level_id')->nullable()->change();

// Ajouter un champ pour le cycle
$table->enum('cycle', ['preprimaire', 'primaire', 'college', 'lycee'])->nullable();

// Ajouter un champ pour indiquer si c'est un frais général
$table->boolean('is_general')->default(false);
```

### **2. API Route**

**Fichier** : `routes/api.php`

**Logique** :
```php
// Si un niveau est fourni, récupérer son cycle
if ($levelId && !$cycle) {
    $level = \App\Models\Level::find($levelId);
    $cycle = $level ? $level->cycle : null;
}

// Récupérer les frais du cycle
$cycleFees = \App\Models\LevelFee::where('cycle', $cycle)
    ->where('is_general', false)
    ->where('is_active', true)
    ->get();

// Récupérer les frais généraux
$generalFees = \App\Models\LevelFee::where('is_general', true)
    ->where('is_active', true)
    ->get();

// Combiner les frais
$allFees = $cycleFees->concat($generalFees);
```

**Réponse** :
```json
{
  "fees": [...],
  "cycle_fees": [...],
  "general_fees": [...],
  "total": 322000,
  "cycle": "primaire"
}
```

### **3. JavaScript**

**Fichier** : `resources/views/enrollments/create.blade.php`

**Fonction `createFeeRow()`** :
```javascript
function createFeeRow(fee) {
    const row = document.createElement('tr');
    const frequencyLabel = fee.frequency === 'monthly' 
        ? ' <span class="badge bg-info">Mensuel</span>' 
        : '';
    
    row.innerHTML = `
        <td>
            <strong>${fee.name}</strong>
            ${fee.is_mandatory ? '<span class="badge bg-danger ms-1">Obligatoire</span>' : ''}
            ${frequencyLabel}
            ${fee.description ? `<br><small class="text-muted">${fee.description}</small>` : ''}
        </td>
        <td class="text-end">
            <strong>${formatNumber(fee.amount)} FCFA</strong>
        </td>
        <td class="text-center">
            <input type="checkbox" 
                   class="form-check-input fee-checkbox" 
                   data-fee-id="${fee.id}" 
                   data-fee-amount="${fee.amount}"
                   ${fee.is_mandatory ? 'checked disabled' : 'checked'}
                   onchange="updateFeesTotal()">
        </td>
    `;
    return row;
}
```

**Affichage avec sections** :
```javascript
// Section frais du cycle
if (data.cycle_fees && data.cycle_fees.length > 0) {
    const cycleHeader = document.createElement('tr');
    cycleHeader.innerHTML = `
        <td colspan="3" class="table-secondary">
            <strong><i class="bi bi-mortarboard me-2"></i>Frais du cycle ${data.cycle}</strong>
        </td>
    `;
    feesList.appendChild(cycleHeader);
    
    data.cycle_fees.forEach(fee => {
        feesList.appendChild(createFeeRow(fee));
    });
}

// Section frais généraux
if (data.general_fees && data.general_fees.length > 0) {
    const generalHeader = document.createElement('tr');
    generalHeader.innerHTML = `
        <td colspan="3" class="table-info">
            <strong><i class="bi bi-gear me-2"></i>Frais généraux (tous cycles)</strong>
        </td>
    `;
    feesList.appendChild(generalHeader);
    
    data.general_fees.forEach(fee => {
        feesList.appendChild(createFeeRow(fee));
    });
}
```

### **4. Seeder**

**Fichier** : `database/seeders/SchoolFeesSeeder.php`

**Statistiques créées** :
- 📊 **17 frais par cycle** (répartis sur 4 cycles)
- 🌐 **6 frais généraux**
- ✅ **23 frais au total**

**Commande** :
```bash
php artisan db:seed --class=SchoolFeesSeeder
```

---

## 🎯 Cas d'Usage

### **Cas 1 : Inscription Primaire Standard**

**Scénario** : Famille qui paie tout sauf transport et cantine

1. Sélectionner "Primaire" → "CP"
2. → Chargement automatique des frais
3. Décocher "Transport scolaire"
4. Décocher "Cantine scolaire"
5. **Total** : 217,000 FCFA (205,000 + 12,000 généraux)
6. Payer 217,000 FCFA
7. Soumettre

### **Cas 2 : Inscription Collège avec Services**

**Scénario** : Famille qui prend tous les services

1. Sélectionner "Collège" → "6ème"
2. → Chargement automatique des frais
3. Tout laisser coché
4. **Total** : 404,000 FCFA (297,000 + 107,000 généraux)
5. Payer 200,000 FCFA (acompte)
6. **Reste** : 204,000 FCFA
7. Soumettre

### **Cas 3 : Inscription Lycée - Paiement Échelonné**

**Scénario** : Famille qui paie uniquement l'obligatoire

1. Sélectionner "Lycée" → "Seconde"
2. → Chargement automatique des frais
3. Décocher tous les frais optionnels
4. **Total** : 367,000 FCFA (355,000 + 12,000 généraux obligatoires)
5. Payer 150,000 FCFA (premier versement)
6. **Reste** : 217,000 FCFA
7. Soumettre

---

## 📊 Statistiques par Cycle

### **Préprimaire**
- Frais obligatoires : **155,000 FCFA**
- Avec frais généraux obligatoires : **167,000 FCFA**
- Avec tous les optionnels : **262,000 FCFA**

### **Primaire**
- Frais obligatoires : **205,000 FCFA**
- Avec frais généraux obligatoires : **217,000 FCFA**
- Avec tous les optionnels : **322,000 FCFA**

### **Collège**
- Frais obligatoires : **285,000 FCFA**
- Avec frais généraux obligatoires : **297,000 FCFA**
- Avec tous les optionnels : **404,000 FCFA**

### **Lycée**
- Frais obligatoires : **355,000 FCFA**
- Avec frais généraux obligatoires : **367,000 FCFA**
- Avec tous les optionnels : **487,000 FCFA**

---

## ✅ Avantages du Système

### **Séparation Cycle / Général**

1. ✅ **Clarté** : Distinction claire entre frais spécifiques et généraux
2. ✅ **Flexibilité** : Les frais généraux s'appliquent à tous les cycles
3. ✅ **Maintenance** : Modifier un frais général affecte tous les cycles
4. ✅ **Évolutivité** : Facile d'ajouter de nouveaux frais

### **Frais Obligatoires / Optionnels**

1. ✅ **Contrôle** : Les frais obligatoires ne peuvent pas être décochés
2. ✅ **Flexibilité** : Les familles choisissent les services optionnels
3. ✅ **Transparence** : Identification claire des frais obligatoires

### **Frais Mensuels**

1. ✅ **Badge "Mensuel"** : Identification visuelle
2. ✅ **Calcul précis** : Montant mensuel affiché (à multiplier par 9 mois)
3. ✅ **Flexibilité** : Possibilité de ne pas prendre ces services

---

## 🧪 Comment Tester

### **Test 1 : Voir les Frais Préprimaire**

1. Aller sur `/enrollments/create`
2. Sélectionner "Préprimaire"
3. Sélectionner "Petite Section"
4. → Vérifier que 3 frais du cycle + 6 frais généraux s'affichent
5. → Total : 167,000 FCFA (avec obligatoires uniquement)

### **Test 2 : Voir les Frais Primaire**

1. Sélectionner "Primaire"
2. Sélectionner "CP"
3. → Vérifier que 4 frais du cycle + 6 frais généraux s'affichent
4. → Total : 322,000 FCFA (tous cochés)

### **Test 3 : Décocher des Frais Optionnels**

1. Après chargement des frais
2. Décocher "Activités sportives"
3. → Total : 312,000 FCFA
4. Décocher "Transport scolaire"
5. → Total : 282,000 FCFA
6. Décocher "Cantine scolaire"
7. → Total : 257,000 FCFA

### **Test 4 : Essayer de Décocher un Frais Obligatoire**

1. Essayer de décocher "Frais de scolarité"
2. → Impossible (checkbox désactivée)
3. → Le frais reste coché

### **Test 5 : Paiement Complet Rapide**

1. Après chargement, total = 322,000 FCFA
2. Cliquer sur "Payer le montant total"
3. → Champ "Montant payé" = 322,000 FCFA
4. → Reste à payer = 0 FCFA

---

## 🔄 Commandes Utiles

### **Créer les frais**
```bash
php artisan db:seed --class=SchoolFeesSeeder
```

### **Recréer les frais (supprime et recrée)**
```bash
php artisan db:seed --class=SchoolFeesSeeder
```

**⚠️ Attention** : Cette commande supprime :
- Tous les frais existants (`level_fees`)
- Tous les frais de classe (`class_fees`)
- Tous les frais d'inscription (`enrollment_fees`)

---

## 📚 Résumé des Frais Créés

### **Par Cycle**

| Cycle | Frais | Obligatoires | Optionnels | Total Obligatoire | Total Complet |
|-------|-------|--------------|------------|-------------------|---------------|
| **Préprimaire** | 3 | 3 | 0 | 155,000 F | 155,000 F |
| **Primaire** | 4 | 3 | 1 | 205,000 F | 215,000 F |
| **Collège** | 5 | 4 | 1 | 285,000 F | 297,000 F |
| **Lycée** | 5 | 4 | 1 | 355,000 F | 380,000 F |

### **Frais Généraux**

| Frais | Montant | Obligatoire | Fréquence |
|-------|---------|-------------|-----------|
| Uniforme complet | 25,000 F | ❌ | Annuel |
| Tenue de sport | 15,000 F | ❌ | Annuel |
| Transport scolaire | 30,000 F | ❌ | **Mensuel** |
| Cantine scolaire | 25,000 F | ❌ | **Mensuel** |
| Assurance scolaire | 10,000 F | ✅ | Annuel |
| Carte d'étudiant | 2,000 F | ✅ | Annuel |

**Total obligatoire** : 12,000 FCFA  
**Total avec optionnels annuels** : 52,000 FCFA  
**Total avec services mensuels (9 mois)** : 547,000 FCFA

---

## ✅ Résultat Final

### **Avant** ❌
- Frais saisis manuellement
- Pas de distinction cycle/général
- Pas de frais obligatoires/optionnels
- Pas de traçabilité

### **Après** ✅
- ✅ **23 frais configurés** dans la base de données
- ✅ **17 frais par cycle** (répartis sur 4 cycles)
- ✅ **6 frais généraux** (applicables à tous)
- ✅ **Chargement automatique** selon le cycle
- ✅ **Affichage en 2 sections** (cycle + généraux)
- ✅ **Badges obligatoires** et mensuels
- ✅ **Sélection interactive** avec checkboxes
- ✅ **Calcul automatique** du total
- ✅ **Gestion des erreurs** avec fallback

**Le système de frais est maintenant complet, professionnel et flexible ! 🎉**

---

**Date de création** : 8 octobre 2025  
**Version** : 1.0  
**Statut** : ✅ Terminé  
**Seeder** : `SchoolFeesSeeder`
