# 💰 Guide - Chargement Automatique des Frais d'Inscription

## 🎯 Vue d'Ensemble

Ce guide documente l'implémentation du système de chargement automatique des frais d'inscription depuis la base de données, remplaçant les champs de saisie manuelle par une sélection interactive des frais configurés par niveau.

---

## ✨ Nouvelle Fonctionnalité

### **Chargement Automatique des Frais par Niveau**

Lorsqu'un utilisateur sélectionne un niveau dans le formulaire d'inscription, le système :
1. ✅ **Charge automatiquement** tous les frais configurés pour ce niveau
2. ✅ **Affiche un tableau interactif** avec tous les frais disponibles
3. ✅ **Permet la sélection** des frais à appliquer (via des checkboxes)
4. ✅ **Calcule automatiquement** le total en fonction des frais sélectionnés
5. ✅ **Met à jour en temps réel** le reste à payer

---

## 🎨 Interface Utilisateur

### **1. Avant la Sélection du Niveau**

```
┌─────────────────────────────────────────────────────┐
│ 💳 Paiement des frais                               │
├─────────────────────────────────────────────────────┤
│                                                     │
│ [Sélectionnez d'abord un cycle et un niveau]       │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### **2. Pendant le Chargement**

```
┌─────────────────────────────────────────────────────┐
│ ℹ️ Chargement des frais...                          │
└─────────────────────────────────────────────────────┘
```

### **3. Après le Chargement (Frais Trouvés)**

```
┌─────────────────────────────────────────────────────┐
│ ✅ Frais pour ce niveau                             │
├─────────────────────────────────────────────────────┤
│ Frais                    │ Montant    │ ☑ Tout      │
├──────────────────────────┼────────────┼─────────────┤
│ Frais de scolarité       │ 150,000 F  │ ☑ (désactivé)│
│ [Obligatoire]            │            │             │
├──────────────────────────┼────────────┼─────────────┤
│ Frais d'inscription      │  25,000 F  │ ☑           │
├──────────────────────────┼────────────┼─────────────┤
│ Uniforme scolaire        │  15,000 F  │ ☑           │
├──────────────────────────┼────────────┼─────────────┤
│ Livres et fournitures    │  20,000 F  │ ☐           │
├──────────────────────────┼────────────┼─────────────┤
│ Total sélectionné        │ 190,000 F  │             │
└─────────────────────────────────────────────────────┘

Frais totaux: 190,000 FCFA (calculé automatiquement)
Le total est calculé automatiquement en fonction des frais sélectionnés

Montant payé: [________] FCFA
[✓ Payer le montant total]

Reste à payer: 0 FCFA
```

### **4. Aucun Frais Configuré**

```
┌─────────────────────────────────────────────────────┐
│ ℹ️ Aucun frais configuré pour ce niveau.            │
│    Vous pouvez saisir manuellement le montant.      │
└─────────────────────────────────────────────────────┘

Frais totaux: [________] FCFA (saisie manuelle activée)
```

### **5. Erreur de Chargement**

```
┌─────────────────────────────────────────────────────┐
│ ⚠️ Erreur lors du chargement des frais.             │
│    Vous pouvez saisir manuellement le montant.      │
└─────────────────────────────────────────────────────┘

Frais totaux: [________] FCFA (saisie manuelle activée)
```

---

## 🔧 Fonctionnalités Détaillées

### **1. Tableau des Frais**

| Colonne | Description |
|---------|-------------|
| **Frais** | Nom du frais + badge "Obligatoire" si applicable + description |
| **Montant** | Montant formaté (ex: 150,000 FCFA) |
| **Checkbox** | Case à cocher pour sélectionner/désélectionner le frais |

**Caractéristiques** :
- ✅ Les frais **obligatoires** sont cochés et désactivés (impossible de les décocher)
- ✅ Les frais **optionnels** sont cochés par défaut mais peuvent être décochés
- ✅ Badge rouge "Obligatoire" pour identifier les frais obligatoires
- ✅ Description affichée sous le nom du frais (si disponible)

### **2. Sélection Globale**

Une checkbox "Tout" dans l'en-tête du tableau permet de :
- ✅ **Cocher** tous les frais optionnels d'un coup
- ✅ **Décocher** tous les frais optionnels d'un coup
- ⚠️ Les frais obligatoires restent toujours cochés

### **3. Calcul Automatique**

Le système calcule automatiquement :
- ✅ **Total sélectionné** : Somme des frais cochés (affiché dans le pied du tableau)
- ✅ **Frais totaux** : Même montant, affiché dans le champ (en lecture seule)
- ✅ **Reste à payer** : Total - Montant payé

**Mise à jour en temps réel** :
- Quand on coche/décoche un frais → Total mis à jour
- Quand on modifie le montant payé → Reste à payer mis à jour

### **4. Bouton "Payer le Montant Total"**

Un bouton pratique qui :
- ✅ Remplit automatiquement le champ "Montant payé" avec le total des frais
- ✅ Met le reste à payer à 0
- ✅ Évite la saisie manuelle pour un paiement complet

---

## 🔌 API et Routes

### **Route API Créée**

```php
GET /api/level-fees?level_id={id}&academic_year_id={id}
```

**Paramètres** :
- `level_id` (requis) : ID du niveau
- `academic_year_id` (optionnel) : ID de l'année scolaire (année en cours par défaut)

**Réponse** :
```json
{
  "fees": [
    {
      "id": 1,
      "name": "Frais de scolarité",
      "amount": 150000,
      "fee_type": "tuition",
      "is_mandatory": true,
      "description": "Frais de scolarité annuels"
    },
    {
      "id": 2,
      "name": "Frais d'inscription",
      "amount": 25000,
      "fee_type": "registration",
      "is_mandatory": true,
      "description": null
    },
    {
      "id": 3,
      "name": "Uniforme scolaire",
      "amount": 15000,
      "fee_type": "uniform",
      "is_mandatory": false,
      "description": "Tenue complète"
    }
  ],
  "total": 190000
}
```

### **Logique de l'API**

```php
// Récupérer les frais actifs pour le niveau
$fees = \App\Models\LevelFee::where('level_id', $levelId)
    ->where('is_active', true)
    ->when($academicYearId, function($query) use ($academicYearId) {
        return $query->where('academic_year_id', $academicYearId);
    })
    ->orderBy('sort_order')
    ->orderBy('name')
    ->get(['id', 'name', 'amount', 'fee_type', 'is_mandatory', 'description']);

// Calculer le total
$total = $fees->sum('amount');
```

---

## 💻 Code JavaScript

### **Fonction de Chargement**

```javascript
function loadLevelFees(levelId) {
    const academicYearId = document.getElementById('academic_year_id').value;
    
    // Afficher le chargement
    document.getElementById('fees-loading-zone').style.display = 'block';
    document.getElementById('fees-display-zone').style.display = 'none';
    
    // Appel API
    fetch(`/api/level-fees?level_id=${levelId}&academic_year_id=${academicYearId}`)
        .then(response => response.json())
        .then(data => {
            // Afficher les frais
            displayFees(data.fees);
            updateFeesTotal();
        })
        .catch(error => {
            // Gérer l'erreur
            showError();
        });
}
```

### **Fonction de Mise à Jour du Total**

```javascript
function updateFeesTotal() {
    const checkboxes = document.querySelectorAll('.fee-checkbox:checked');
    let total = 0;
    
    checkboxes.forEach(checkbox => {
        total += parseFloat(checkbox.dataset.feeAmount);
    });
    
    // Mettre à jour l'affichage
    document.getElementById('selected-fees-total').textContent = formatNumber(total) + ' FCFA';
    document.getElementById('total_fees').value = total;
    
    // Recalculer le reste à payer
    calculateBalance();
}
```

### **Fonction "Payer le Montant Total"**

```javascript
function payFullAmount() {
    const totalFees = parseFloat(document.getElementById('total_fees').value) || 0;
    document.getElementById('amount_paid').value = totalFees;
    calculateBalance();
}
```

---

## 📊 Structure de la Base de Données

### **Table `level_fees`**

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | ID unique |
| `level_id` | bigint | ID du niveau (FK) |
| `academic_year_id` | bigint | ID de l'année scolaire (FK) |
| `fee_type` | enum | Type de frais (tuition, registration, uniform, etc.) |
| `name` | string | Nom du frais |
| `description` | text | Description (optionnel) |
| `amount` | decimal | Montant |
| `frequency` | enum | Fréquence (monthly, quarterly, yearly, one_time) |
| `due_date` | date | Date d'échéance (optionnel) |
| `is_mandatory` | boolean | Frais obligatoire ? |
| `is_active` | boolean | Frais actif ? |
| `sort_order` | integer | Ordre d'affichage |

---

## 🎯 Cas d'Usage

### **Cas 1 : Inscription Normale avec Frais Configurés**

**Étapes** :
1. Utilisateur sélectionne le cycle "Primaire"
2. Utilisateur sélectionne le niveau "CP"
3. → Le système charge automatiquement les frais pour le CP
4. → Affichage du tableau avec 4 frais :
   - Scolarité (150,000 F) - Obligatoire ☑
   - Inscription (25,000 F) - Obligatoire ☑
   - Uniforme (15,000 F) - Optionnel ☑
   - Livres (20,000 F) - Optionnel ☑
5. → Total : 210,000 FCFA
6. Utilisateur décoche "Livres" (famille fournit les livres)
7. → Total mis à jour : 190,000 FCFA
8. Utilisateur clique sur "Payer le montant total"
9. → Montant payé : 190,000 FCFA
10. → Reste à payer : 0 FCFA
11. Soumission du formulaire

### **Cas 2 : Paiement Partiel**

**Étapes** :
1. Après chargement des frais, total = 190,000 FCFA
2. Utilisateur saisit 100,000 FCFA dans "Montant payé"
3. → Reste à payer : 90,000 FCFA (calculé automatiquement)
4. Soumission du formulaire
5. → Statut de paiement : "Partiel"

### **Cas 3 : Aucun Frais Configuré**

**Étapes** :
1. Utilisateur sélectionne un niveau sans frais configurés
2. → Message : "Aucun frais configuré pour ce niveau"
3. → Le champ "Frais totaux" devient éditable
4. Utilisateur saisit manuellement : 150,000 FCFA
5. Utilisateur saisit le montant payé : 150,000 FCFA
6. Soumission du formulaire

### **Cas 4 : Erreur de Connexion**

**Étapes** :
1. Utilisateur sélectionne un niveau
2. → Erreur lors de l'appel API (pas de connexion)
3. → Message : "Erreur lors du chargement des frais"
4. → Le champ "Frais totaux" devient éditable
5. Utilisateur saisit manuellement le montant
6. Soumission du formulaire

---

## ✅ Avantages du Système

### **Pour les Administrateurs**

1. ✅ **Centralisation** : Les frais sont configurés une seule fois dans la base de données
2. ✅ **Cohérence** : Tous les utilisateurs voient les mêmes frais pour un niveau donné
3. ✅ **Flexibilité** : Possibilité de définir des frais obligatoires et optionnels
4. ✅ **Historique** : Les frais sont liés à une année scolaire spécifique
5. ✅ **Maintenance** : Modification des frais sans toucher au code

### **Pour les Utilisateurs**

1. ✅ **Rapidité** : Plus besoin de saisir manuellement tous les frais
2. ✅ **Précision** : Moins d'erreurs de saisie
3. ✅ **Transparence** : Tous les frais sont clairement affichés
4. ✅ **Flexibilité** : Possibilité de décocher les frais optionnels
5. ✅ **Calcul automatique** : Le total se met à jour en temps réel

### **Pour le Système**

1. ✅ **Traçabilité** : Chaque frais est enregistré avec son ID
2. ✅ **Reporting** : Statistiques précises par type de frais
3. ✅ **Évolutivité** : Facile d'ajouter de nouveaux types de frais
4. ✅ **Robustesse** : Gestion des erreurs et fallback sur saisie manuelle

---

## 🔄 Workflow Complet

```
┌─────────────────────────────────────────────────────────┐
│ 1. Utilisateur sélectionne le cycle                    │
└───────────────────┬─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 2. Utilisateur sélectionne le niveau                   │
└───────────────────┬─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 3. Appel API : GET /api/level-fees?level_id=X          │
└───────────────────┬─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 4. Affichage du tableau des frais                      │
│    - Frais obligatoires cochés et désactivés           │
│    - Frais optionnels cochés par défaut                │
└───────────────────┬─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 5. Utilisateur coche/décoche les frais optionnels      │
└───────────────────┬─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 6. Calcul automatique du total                         │
└───────────────────┬─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 7. Utilisateur saisit le montant payé                  │
│    (ou clique sur "Payer le montant total")            │
└───────────────────┬─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 8. Calcul automatique du reste à payer                 │
└───────────────────┬─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ 9. Soumission du formulaire                            │
└─────────────────────────────────────────────────────────┘
```

---

## 📋 Checklist de Validation

### **Chargement des Frais**
- [ ] Les frais se chargent automatiquement quand on sélectionne un niveau
- [ ] Le message de chargement s'affiche pendant l'appel API
- [ ] Le tableau s'affiche correctement avec tous les frais
- [ ] Les frais obligatoires ont le badge "Obligatoire"
- [ ] Les descriptions s'affichent si présentes

### **Sélection des Frais**
- [ ] Les frais obligatoires sont cochés et désactivés
- [ ] Les frais optionnels sont cochés par défaut
- [ ] On peut décocher les frais optionnels
- [ ] La checkbox "Tout" coche/décoche tous les frais optionnels
- [ ] Les frais obligatoires restent cochés même avec "Tout"

### **Calculs Automatiques**
- [ ] Le total sélectionné se met à jour en temps réel
- [ ] Le champ "Frais totaux" est en lecture seule
- [ ] Le champ "Frais totaux" affiche le bon montant
- [ ] Le reste à payer se calcule automatiquement
- [ ] Le bouton "Payer le montant total" fonctionne

### **Gestion des Erreurs**
- [ ] Message approprié si aucun frais configuré
- [ ] Le champ devient éditable si aucun frais
- [ ] Message d'erreur si problème de connexion
- [ ] Le champ devient éditable en cas d'erreur
- [ ] Fallback sur saisie manuelle fonctionne

---

## 🚀 Résultat Final

### **Avant** ❌
- Saisie manuelle des frais totaux
- Risque d'erreurs de saisie
- Pas de traçabilité des frais individuels
- Montants incohérents entre les inscriptions
- Pas de distinction obligatoire/optionnel

### **Après** ✅
- ✅ **Chargement automatique** des frais depuis la BD
- ✅ **Tableau interactif** avec sélection des frais
- ✅ **Calcul automatique** du total
- ✅ **Frais obligatoires** identifiés et verrouillés
- ✅ **Frais optionnels** sélectionnables
- ✅ **Traçabilité complète** de chaque frais
- ✅ **Cohérence** des montants
- ✅ **Gestion des erreurs** avec fallback
- ✅ **Bouton rapide** pour paiement complet

**Le système est maintenant professionnel, fiable et facile à utiliser !** 🎉

---

**Date de création** : 8 octobre 2025  
**Version** : 1.0  
**Statut** : ✅ Terminé
