# Système de Niveaux Scolaires Flexibles

## 📚 Vue d'ensemble

Ce système permet de gérer de manière flexible différents niveaux scolaires avec des noms différents selon le type d'établissement. Vous pouvez activer/désactiver les niveaux et utiliser des noms spécifiques pour chaque **GROUPE** de niveaux.

## 🎯 Objectif

Permettre à un établissement de gérer :
- **Uniquement le primaire** (préprimaire + primaire)
- **Uniquement le secondaire** (collège + lycée)
- **Les deux** (primaire ET secondaire)

Avec des noms différents selon le **GROUPE** :

### 📗 GROUPE PRIMAIRE
- **Préprimaire + Primaire** = UN SEUL NOM
- Utilisent le champ `primary_school_name`
- Exemple : "Complexe Scolaire Les Étoiles"

### 📘 GROUPE SECONDAIRE
- **Collège + Lycée** = UN SEUL NOM
- Utilisent le champ `secondary_school_name`
- Exemple : "Lycée d'Excellence"

> **⚠️ Important** : Préprimaire et Primaire partagent le MÊME nom, tout comme Collège et Lycée partagent le MÊME nom.

## 📋 Configuration dans les Paramètres

### 1. Accéder aux Paramètres
```
Menu → Administration → Paramètres de l'Établissement
```

### 2. Section "Configuration des Niveaux Scolaires"

Vous y trouverez :

#### **Niveaux actifs**
- ☑️ **Préprimaire (Maternelle)** - Active les classes de maternelle
- ☑️ **Primaire** - Active les classes du CP au CM2
- ☑️ **Secondaire (Collège/Lycée)** - Active les classes de la 6ème à la Terminale

#### **Noms spécifiques par niveau**

**Nom du Complexe Scolaire (Préprimaire + Primaire)**
- Champ : `primary_school_name`
- Exemple : "Complexe Scolaire Les Étoiles"
- Utilisé pour : Documents du préprimaire et primaire (bulletins, attestations, etc.)

**Nom du Collège/Lycée (Secondaire)**
- Champ : `secondary_school_name`
- Exemple : "Lycée d'Excellence de Libreville"
- Utilisé pour : Documents du collège et lycée (bulletins, attestations, etc.)

## 💡 Cas d'usage

### Cas 1 : Établissement avec uniquement le primaire

```
Configuration :
✅ Préprimaire
✅ Primaire
❌ Secondaire

Noms :
- Nom général : "École Les Étoiles"
- Nom primaire : "Complexe Scolaire Les Étoiles"
- Nom secondaire : (vide)

Résultat :
- Tous les documents utilisent "Complexe Scolaire Les Étoiles"
```

### Cas 2 : Établissement avec uniquement le secondaire

```
Configuration :
❌ Préprimaire
❌ Primaire
✅ Secondaire

Noms :
- Nom général : "Lycée Victor Hugo"
- Nom primaire : (vide)
- Nom secondaire : "Lycée Victor Hugo"

Résultat :
- Tous les documents utilisent "Lycée Victor Hugo"
```

### Cas 3 : Établissement complet (primaire + secondaire)

```
Configuration :
✅ Préprimaire
✅ Primaire
✅ Secondaire

Noms :
- Nom général : "Groupe Scolaire La Réussite"
- Nom primaire : "Complexe Scolaire La Réussite"
- Nom secondaire : "Lycée La Réussite"

Résultat :
- Documents préprimaire/primaire : "Complexe Scolaire La Réussite"
- Documents collège/lycée : "Lycée La Réussite"
```

## 💻 Utilisation dans le Code

### 1. Dans les Contrôleurs

```php
use App\Helpers\SchoolHelper;
use App\Models\SchoolSettings;

// Obtenir le nom selon le cycle
$schoolName = SchoolHelper::getSchoolNameByCycle('primaire');
// Retourne : "Complexe Scolaire Les Étoiles"

$schoolName = SchoolHelper::getSchoolNameByCycle('lycee');
// Retourne : "Lycée d'Excellence"

// Obtenir le nom selon un niveau (Level model)
$level = Level::find($levelId);
$schoolName = SchoolHelper::getSchoolNameByLevel($level);

// Vérifier si un niveau est actif
if (SchoolHelper::isLevelActive('primaire')) {
    // Le primaire est activé
}

// Obtenir tous les niveaux actifs
$activeLevels = SchoolHelper::getActiveLevels();
// Retourne : ['preprimaire', 'primaire', 'college', 'lycee']
```

### 2. Dans les Vues Blade

```blade
{{-- Afficher le nom selon le cycle --}}
{{ App\Helpers\SchoolHelper::getSchoolNameByCycle($class->level->cycle) }}

{{-- Vérifier si un niveau est actif --}}
@if(App\Helpers\SchoolHelper::isLevelActive('primaire'))
    <div>Le primaire est activé</div>
@endif

{{-- Afficher le nom selon un enrollment --}}
{{ App\Helpers\SchoolHelper::getSchoolNameByLevel($enrollment->schoolClass->level) }}
```

### 3. Génération de Documents PDF

#### Exemple : Bulletin de notes

```php
// Dans le contrôleur de bulletins
public function generateBulletin($enrollmentId)
{
    $enrollment = Enrollment::with('schoolClass.level')->findOrFail($enrollmentId);
    $level = $enrollment->schoolClass->level;
    
    // Obtenir le bon nom d'établissement selon le niveau
    $schoolName = SchoolHelper::getSchoolNameByLevel($level);
    
    $pdf = PDF::loadView('bulletins.pdf', [
        'enrollment' => $enrollment,
        'schoolName' => $schoolName, // Nom spécifique au niveau
        'schoolLogo' => SchoolHelper::getLogo(),
        // ... autres données
    ]);
    
    return $pdf->download('bulletin.pdf');
}
```

## 🔧 Structure de la Base de Données

### Table `school_settings`

| Champ | Type | Description |
|-------|------|-------------|
| `has_preprimary` | Boolean | Active le préprimaire |
| `has_primary` | Boolean | Active le primaire |
| `has_secondary` | Boolean | Active le secondaire |
| `primary_school_name` | String | Nom du complexe scolaire (optionnel) |
| `secondary_school_name` | String | Nom du collège/lycée (optionnel) |

### Valeurs par défaut

Lors de la création initiale :
- `has_preprimary` = `true`
- `has_primary` = `true`
- `has_secondary` = `true`
- `primary_school_name` = `null`
- `secondary_school_name` = `null`

## 📊 Schéma de Regroupement des Niveaux

```
┌──────────────────────────────────────────────────────────────────┐
│                    ÉTABLISSEMENT SCOLAIRE                         │
└───────────────────────┬──────────────────────────────────────────┘
                        │
          ┌─────────────┴─────────────┐
          │                           │
    ┌─────▼─────┐              ┌──────▼──────┐
    │  GROUPE   │              │   GROUPE    │
    │ PRIMAIRE  │              │ SECONDAIRE  │
    └───────────┘              └─────────────┘
          │                           │
    ┌─────┴─────┐              ┌─────┴─────┐
    │           │              │           │
    ▼           ▼              ▼           ▼
Préprimaire  Primaire      Collège      Lycée
(Maternelle) (CP→CM2)      (6ème→3ème) (2nde→Term)
    │           │              │           │
    └─────┬─────┘              └─────┬─────┘
          │                           │
          ▼                           ▼
  primary_school_name         secondary_school_name
  "Complexe Scolaire..."      "Lycée..."
          │                           │
       Si vide                     Si vide
          │                           │
          └───────────┬───────────────┘
                      ▼
               school_name
            (nom général)
```

## 📊 Logique de Sélection du Nom

```
Élève en Maternelle (PS/MS/GS)
    ↓
Cycle = preprimaire
    ↓
primary_school_name OU school_name

Élève en Primaire (CP/CE1/CE2/CM1/CM2)
    ↓
Cycle = primaire
    ↓
primary_school_name OU school_name

Élève au Collège (6ème/5ème/4ème/3ème)
    ↓
Cycle = college
    ↓
secondary_school_name OU school_name

Élève au Lycée (2nde/1ère/Term)
    ↓
Cycle = lycee
    ↓
secondary_school_name OU school_name
```

## ⚙️ Méthodes du Modèle SchoolSettings

### `getSchoolNameByCycle($cycle)`
Retourne le nom approprié selon le cycle.

**Paramètres :**
- `$cycle` (string) : 'preprimaire', 'primaire', 'college', 'lycee'

**Retour :**
- Nom du complexe scolaire pour préprimaire/primaire
- Nom du collège/lycée pour collège/lycée
- Nom général si le nom spécifique n'est pas défini

### `getSchoolNameByLevel($level)`
Retourne le nom approprié selon un objet Level.

**Paramètres :**
- `$level` (Level|int) : Instance de Level ou ID

**Retour :**
- Nom approprié selon le cycle du niveau

### `isLevelActive($cycle)`
Vérifie si un niveau est actif.

**Paramètres :**
- `$cycle` (string) : 'preprimaire', 'primaire', 'college', 'lycee'

**Retour :**
- `true` si le niveau est actif, `false` sinon

### `getActiveLevels()`
Retourne la liste des niveaux actifs.

**Retour :**
- Array : `['preprimaire', 'primaire', 'college', 'lycee']` (selon configuration)

## 📝 Exemple Complet

### Configuration de l'établissement

```php
// Paramètres enregistrés
$settings = SchoolSettings::getSettings();
$settings->update([
    'school_name' => 'Groupe Scolaire Excellence',
    'primary_school_name' => 'Complexe Scolaire Excellence',
    'secondary_school_name' => 'Lycée d\'Excellence',
    'has_preprimary' => true,
    'has_primary' => true,
    'has_secondary' => true,
]);
```

### Génération d'un bulletin pour un élève du CE2

```php
$enrollment = Enrollment::find(1);
// Classe : CE2 (cycle = primaire)

$schoolName = SchoolHelper::getSchoolNameByLevel($enrollment->schoolClass->level);
// Résultat : "Complexe Scolaire Excellence"
```

### Génération d'un bulletin pour un élève de Terminale

```php
$enrollment = Enrollment::find(2);
// Classe : Terminale D (cycle = lycee)

$schoolName = SchoolHelper::getSchoolNameByLevel($enrollment->schoolClass->level);
// Résultat : "Lycée d'Excellence"
```

## 🎨 Interface Utilisateur

L'interface permet de configurer visuellement les niveaux avec :
- **Switches** pour activer/désactiver chaque niveau
- **Champs de texte** pour les noms spécifiques
- **Info-bulles** pour guider l'utilisateur
- **Placeholders** avec des exemples concrets

## 🔄 Migration

Pour mettre à jour une installation existante :

```bash
php artisan migrate
```

Cela ajoutera automatiquement les 5 nouveaux champs à la table `school_settings`.

## ✅ Validation

Le système valide automatiquement :
- Au moins un niveau doit être actif
- Les noms spécifiques sont optionnels
- Si un nom spécifique est vide, le nom général est utilisé

## 📞 Support

Pour toute question sur ce système :
- Consulter `app/Models/SchoolSettings.php`
- Consulter `app/Helpers/SchoolHelper.php`
- Voir les exemples dans les contrôleurs de bulletins et documents

## 🎯 Avantages

1. **Flexibilité** : Gérez uniquement ce dont vous avez besoin
2. **Professionnalisme** : Noms différents selon le niveau
3. **Simplicité** : Configuration en quelques clics
4. **Automatique** : Les documents utilisent automatiquement le bon nom
5. **Évolutif** : Ajoutez ou retirez des niveaux à tout moment

