# 📚 Guide : Regroupement des Niveaux Scolaires

## 🎯 Principe Principal

**LES NIVEAUX SONT REGROUPÉS EN DEUX GROUPES :**

```
┌─────────────────────────────────────────────┐
│         🏫 VOTRE ÉTABLISSEMENT               │
│                                              │
│   ┌─────────────────┐  ┌────────────────┐  │
│   │  📗 GROUPE 1    │  │  📘 GROUPE 2   │  │
│   │    PRIMAIRE     │  │   SECONDAIRE   │  │
│   ├─────────────────┤  ├────────────────┤  │
│   │ • Préprimaire   │  │ • Collège      │  │
│   │ • Primaire      │  │ • Lycée        │  │
│   └─────────────────┘  └────────────────┘  │
│         ↓                      ↓             │
│   MÊME NOM pour          MÊME NOM pour      │
│   les 2 niveaux          les 2 niveaux      │
└─────────────────────────────────────────────┘
```

## ✅ Ce qu'il faut retenir

### 📗 GROUPE PRIMAIRE

| Niveau | Classes | Nom utilisé |
|--------|---------|-------------|
| **Préprimaire** | Petite Section, Moyenne Section, Grande Section | `primary_school_name` |
| **Primaire** | CP, CE1, CE2, CM1, CM2 | `primary_school_name` |

> ⚠️ **IMPORTANT** : Ces 2 niveaux utilisent le **MÊME NOM**

### 📘 GROUPE SECONDAIRE

| Niveau | Classes | Nom utilisé |
|--------|---------|-------------|
| **Collège** | 6ème, 5ème, 4ème, 3ème | `secondary_school_name` |
| **Lycée** | 2nde, 1ère, Terminale | `secondary_school_name` |

> ⚠️ **IMPORTANT** : Ces 2 niveaux utilisent le **MÊME NOM**

## 🔍 Exemples Concrets

### Exemple 1 : Établissement Complet

```
Configuration :
─────────────
Nom général : "Groupe Scolaire La Réussite"
Nom primaire : "Complexe Scolaire La Réussite"  ← Pour Préprimaire + Primaire
Nom secondaire : "Lycée La Réussite"           ← Pour Collège + Lycée

Résultats sur les documents :
────────────────────────────
✅ Préprimaire :
   • Bulletin de Petite Section  → "Complexe Scolaire La Réussite"
   • Bulletin de Grande Section  → "Complexe Scolaire La Réussite"

✅ Primaire :
   • Bulletin de CP              → "Complexe Scolaire La Réussite"
   • Bulletin de CE2             → "Complexe Scolaire La Réussite"
   • Bulletin de CM2             → "Complexe Scolaire La Réussite"

✅ Collège :
   • Bulletin de 6ème            → "Lycée La Réussite"
   • Bulletin de 3ème            → "Lycée La Réussite"

✅ Lycée :
   • Bulletin de 2nde            → "Lycée La Réussite"
   • Bulletin de Terminale       → "Lycée La Réussite"
```

### Exemple 2 : Uniquement Primaire

```
Configuration :
─────────────
Nom général : "École Les Étoiles"
Nom primaire : "Complexe Scolaire Les Étoiles"
Nom secondaire : (vide)

Niveaux actifs :
✅ Préprimaire
✅ Primaire
❌ Secondaire

Résultats :
───────────
• Tous les documents → "Complexe Scolaire Les Étoiles"
```

### Exemple 3 : Uniquement Secondaire

```
Configuration :
─────────────
Nom général : "Lycée Victor Hugo"
Nom primaire : (vide)
Nom secondaire : "Lycée Victor Hugo"

Niveaux actifs :
❌ Préprimaire
❌ Primaire
✅ Secondaire

Résultats :
───────────
• Tous les documents → "Lycée Victor Hugo"
```

## 🖥️ Interface Utilisateur

Dans les **Paramètres de l'Établissement**, vous verrez :

```
┌──────────────────────────────────────────────────┐
│ Configuration des Niveaux Scolaires              │
├──────────────────────────────────────────────────┤
│                                                   │
│  Niveaux actifs                                  │
│                                                   │
│  ┌──────────────────┐  ┌──────────────────┐     │
│  │ 📗 GROUPE        │  │ 📘 GROUPE        │     │
│  │  PRIMAIRE        │  │  SECONDAIRE      │     │
│  │                  │  │                  │     │
│  │ [✓] PRIMAIRE     │  │ [✓] SECONDAIRE   │     │
│  │                  │  │                  │     │
│  │ Préprimaire      │  │ Collège          │     │
│  │ (Maternelle)     │  │ (6ème à 3ème)    │     │
│  │ Primaire         │  │ Lycée            │     │
│  │ (CP à CM2)       │  │ (2nde à Term)    │     │
│  │                  │  │                  │     │
│  │ Active les 2     │  │ Active les 2     │     │
│  │ en même temps    │  │ en même temps    │     │
│  └──────────────────┘  └──────────────────┘     │
│                                                   │
│  📝 Noms des Établissements                      │
│                                                   │
│  ┌──────────────────┐  ┌──────────────────┐     │
│  │ Nom du Complexe  │  │ Nom du Lycée     │     │
│  │ Scolaire         │  │                  │     │
│  │ [______________] │  │ [______________] │     │
│  │                  │  │                  │     │
│  │ Utilisé pour:    │  │ Utilisé pour:    │     │
│  │ • Préprimaire ET │  │ • Collège ET     │     │
│  │ • Primaire       │  │ • Lycée          │     │
│  └──────────────────┘  └──────────────────┘     │
└──────────────────────────────────────────────────┘
```

> **⚠️ Important** : Il y a **UN SEUL CHECKBOX** par groupe !
> - ☑ PRIMAIRE = Active Préprimaire + Primaire ensemble
> - ☑ SECONDAIRE = Active Collège + Lycée ensemble

## 💡 Pourquoi ce regroupement ?

### Au Gabon et dans de nombreux pays :

1. **Préprimaire + Primaire** = **"École Primaire"** ou **"Complexe Scolaire"**
   - C'est le même établissement
   - Même directeur
   - Même administration
   - Documents avec le même en-tête

2. **Collège + Lycée** = **"Lycée"** ou **"Collège/Lycée"**
   - C'est le même établissement secondaire
   - Même proviseur
   - Même administration
   - Documents avec le même en-tête

## 📋 Comment configurer ?

### Étape 1 : Accéder aux paramètres
```
Menu → Administration → Paramètres de l'Établissement
```

### Étape 2 : Activer les groupes de niveaux
Cochez les cases des GROUPES que vous gérez :
- Si vous gérez la maternelle et le primaire → Cochez **☑ PRIMAIRE** (active automatiquement Préprimaire + Primaire)
- Si vous gérez le collège et le lycée → Cochez **☑ SECONDAIRE** (active automatiquement Collège + Lycée)

### Étape 3 : Définir les noms
- **Nom du Complexe Scolaire** : Pour les documents du préprimaire ET primaire
- **Nom du Collège/Lycée** : Pour les documents du collège ET lycée

### Étape 4 : Enregistrer
Cliquez sur **Enregistrer** en bas de la page.

## ❓ Questions Fréquentes

### Q : Puis-je activer uniquement le préprimaire SANS le primaire ?
**R :** Non, c'est UN SEUL GROUPE. Le checkbox **PRIMAIRE** active les deux niveaux ensemble.

### Q : Puis-je activer uniquement le collège SANS le lycée ?
**R :** Non, c'est UN SEUL GROUPE. Le checkbox **SECONDAIRE** active les deux niveaux ensemble.

### Q : Puis-je avoir des noms différents pour préprimaire et primaire ?
**R :** Non, ils utilisent le MÊME nom car ils forment un seul groupe (le primaire).

### Q : Puis-je avoir des noms différents pour collège et lycée ?
**R :** Non, ils utilisent le MÊME nom car ils forment un seul groupe (le secondaire).

### Q : Que se passe-t-il si je laisse les champs vides ?
**R :** Le système utilisera le **nom général de l'établissement** pour tous les documents.

### Q : Puis-je gérer uniquement le primaire ?
**R :** Oui ! Cochez uniquement **☑ PRIMAIRE**, décochez **SECONDAIRE**.

### Q : Puis-je gérer uniquement le secondaire ?
**R :** Oui ! Cochez uniquement **☑ SECONDAIRE**, décochez **PRIMAIRE**.

## ✨ Avantages

✅ **Professionnalisme** : Noms différents selon le niveau d'enseignement
✅ **Clarté** : Documents bien identifiés (primaire vs secondaire)
✅ **Flexibilité** : Gérez uniquement ce dont vous avez besoin
✅ **Automatique** : Les documents utilisent automatiquement le bon nom
✅ **Simple** : Configuration en 2 minutes

## 🎯 En résumé

```
2 CHECKBOXES = 2 GROUPES = 2 NOMS

☑ PRIMAIRE
   ↓
Préprimaire + Primaire
   ↓
1 seul nom partagé
"Complexe Scolaire..."

☑ SECONDAIRE
   ↓
Collège + Lycée
   ↓
1 seul nom partagé
"Lycée..."
```

> **⚠️ IMPORTANT** :
> - **1 checkbox PRIMAIRE** = Active Préprimaire ET Primaire en même temps
> - **1 checkbox SECONDAIRE** = Active Collège ET Lycée en même temps
> - **Vous ne pouvez PAS les activer séparément !**

**C'est aussi simple que ça !** 🎉

