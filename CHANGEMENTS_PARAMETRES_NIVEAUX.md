# 📋 Changements : Paramètres d'Établissement Simplifiés

## 🎯 Résumé des Changements

Les paramètres d'établissement ont été **simplifiés et réorganisés** pour mieux refléter la structure réelle des établissements scolaires au Gabon.

## ❌ Champs Supprimés

Les champs suivants ont été **retirés** de l'interface des paramètres :

| Champ supprimé | Raison |
|----------------|--------|
| **Nom de l'établissement** | Remplacé par les noms spécifiques par groupe (Primaire/Secondaire) |
| **Type d'établissement** | Non nécessaire, déterminé par les niveaux actifs |
| **Niveau** | Non nécessaire, déterminé par les niveaux actifs |

> **Note** : Ces champs sont conservés en base de données (champs cachés) pour la compatibilité avec l'ancien système, mais ne sont plus visibles ni modifiables.

## ✅ Nouvelle Structure

### 1. **Informations Générales** (Simplifié)

Contient maintenant **uniquement** :
- ✅ **Année scolaire** (Format: 2025-2026)

### 2. **Configuration des Niveaux Scolaires** (Section principale)

#### **Niveaux actifs** (2 checkboxes)

| Checkbox | Active | Nom du champ |
|----------|--------|--------------|
| ☑ **PRIMAIRE** | Préprimaire + Primaire | `has_primary` |
| ☑ **SECONDAIRE** | Collège + Lycée | `has_secondary` |

#### **Noms des Établissements** (OBLIGATOIRES)

| Champ | Obligatoire | Utilisé pour |
|-------|-------------|--------------|
| **Nom du Complexe Scolaire** * | ✅ Oui | Préprimaire ET Primaire |
| **Nom du Collège/Lycée** * | ✅ Oui | Collège ET Lycée |

## 📊 Comparaison Avant/Après

### AVANT (Ancien système)

```
┌─────────────────────────────────────┐
│ Informations Générales              │
├─────────────────────────────────────┤
│ • Nom de l'établissement *          │
│ • Type d'établissement *            │
│ • Niveau *                          │
│ • Année scolaire *                  │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ Configuration Niveaux (optionnel)   │
├─────────────────────────────────────┤
│ • Nom primaire (optionnel)          │
│ • Nom secondaire (optionnel)        │
└─────────────────────────────────────┘
```

### MAINTENANT (Nouveau système)

```
┌─────────────────────────────────────┐
│ Informations Générales              │
├─────────────────────────────────────┤
│ • Année scolaire *                  │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ Configuration des Niveaux           │
├─────────────────────────────────────┤
│ Niveaux actifs:                     │
│ ☑ PRIMAIRE (Préprimaire + Primaire)│
│ ☑ SECONDAIRE (Collège + Lycée)     │
│                                     │
│ Noms des Établissements:            │
│ • Nom Complexe Scolaire * (OBLIG)  │
│ • Nom Collège/Lycée * (OBLIG)      │
└─────────────────────────────────────┘
```

## 🔍 Impact sur le Code

### 1. **Formulaire des Paramètres**

**Fichier** : `resources/views/admin/settings/index.blade.php`

**Changements** :
- ✅ Supprimé les champs `school_name`, `school_type`, `school_level`
- ✅ Ajouté des champs cachés pour compatibilité
- ✅ Rendu obligatoires les champs `primary_school_name` et `secondary_school_name`

### 2. **Modèle SchoolSettings**

**Fichier** : `app/Models/SchoolSettings.php`

**Changements** :
- ✅ Méthode `getSchoolNameByCycle()` : Ne fallback plus sur `school_name`
- ✅ Méthode `getSchoolNameByLevel()` : Utilise les noms spécifiques en priorité
- ✅ Fallback : `primary_school_name` → `secondary_school_name` → 'Établissement Scolaire'

### 3. **Helper SchoolHelper**

**Fichier** : `app/Helpers/SchoolHelper.php`

**Changements** :
- ✅ Méthode `getName()` : Retourne le nom du premier groupe actif
- ✅ Priorité : Nom primaire (si actif) → Nom secondaire (si actif) → Fallback

## 📝 Exemples d'Utilisation

### Configuration d'un établissement complet

```
Configuration dans les paramètres :
────────────────────────────────────
☑ PRIMAIRE (activé)
   Nom : "Complexe Scolaire La Réussite"

☑ SECONDAIRE (activé)
   Nom : "Lycée La Réussite"

Résultats dans l'application :
──────────────────────────────
• Bulletin de Maternelle  → "Complexe Scolaire La Réussite"
• Bulletin de CE2         → "Complexe Scolaire La Réussite"
• Bulletin de 3ème        → "Lycée La Réussite"
• Bulletin de Terminale   → "Lycée La Réussite"

• SchoolHelper::getName() → "Complexe Scolaire La Réussite"
  (retourne le nom du premier groupe actif)
```

### Configuration primaire uniquement

```
Configuration dans les paramètres :
────────────────────────────────────
☑ PRIMAIRE (activé)
   Nom : "École Les Étoiles"

☐ SECONDAIRE (désactivé)
   Nom : (vide)

Résultats dans l'application :
──────────────────────────────
• Tous les documents      → "École Les Étoiles"
• SchoolHelper::getName() → "École Les Étoiles"
```

### Configuration secondaire uniquement

```
Configuration dans les paramètres :
────────────────────────────────────
☐ PRIMAIRE (désactivé)
   Nom : (vide)

☑ SECONDAIRE (activé)
   Nom : "Lycée Victor Hugo"

Résultats dans l'application :
──────────────────────────────
• Tous les documents      → "Lycée Victor Hugo"
• SchoolHelper::getName() → "Lycée Victor Hugo"
```

## ⚠️ Points d'Attention

### 1. **Champs Obligatoires**

Les champs `primary_school_name` et `secondary_school_name` sont maintenant **OBLIGATOIRES** dans le formulaire :

```html
<input type="text" name="primary_school_name" required>
<input type="text" name="secondary_school_name" required>
```

### 2. **Compatibilité**

Les anciens champs sont conservés comme champs cachés avec des valeurs par défaut :

```html
<input type="hidden" name="school_name" value="Établissement Scolaire">
<input type="hidden" name="school_type" value="École">
<input type="hidden" name="school_level" value="Primaire">
```

### 3. **Migration des Données Existantes**

Si vous avez des établissements déjà configurés :

1. ✅ Les anciennes données sont préservées
2. ✅ Les champs `primary_school_name` et `secondary_school_name` peuvent être vides (pour l'instant)
3. ⚠️ Il est **fortement recommandé** de remplir ces champs dès que possible

## 🔄 Comment Mettre à Jour

### Étape 1 : Accéder aux paramètres
```
Menu → Administration → Paramètres de l'Établissement
```

### Étape 2 : Configurer les niveaux
1. Cochez **☑ PRIMAIRE** si vous gérez le préprimaire/primaire
2. Cochez **☑ SECONDAIRE** si vous gérez le collège/lycée

### Étape 3 : Définir les noms (OBLIGATOIRE)
1. **Nom du Complexe Scolaire** : Pour le primaire
2. **Nom du Collège/Lycée** : Pour le secondaire

### Étape 4 : Enregistrer
Cliquez sur **Enregistrer** en bas de la page.

## ✅ Avantages du Nouveau Système

| Avantage | Explication |
|----------|-------------|
| 🎯 **Plus Simple** | Moins de champs à remplir |
| 📊 **Plus Clair** | Structure organisée par groupes |
| ✨ **Plus Logique** | Correspond à la réalité des établissements |
| 🔒 **Plus Sûr** | Champs obligatoires = pas d'oublis |
| 📝 **Plus Précis** | Noms spécifiques par niveau d'enseignement |

## 📚 Documents Associés

- `SYSTEME_NIVEAUX_SCOLAIRES_FLEXIBLES.md` - Documentation technique complète
- `GUIDE_REGROUPEMENT_NIVEAUX.md` - Guide visuel pour les utilisateurs

## 🎉 Conclusion

Le nouveau système est :
- ✅ **Plus simple** à utiliser
- ✅ **Plus clair** dans sa présentation
- ✅ **Plus conforme** à la réalité des établissements scolaires au Gabon
- ✅ **Obligatoire** pour garantir la qualité des documents

**Tous les documents (bulletins, attestations, certificats) utiliseront automatiquement les bons noms !** 📄✨

