# Système d'Années Scolaires - Calendrier Gabonais

## 📅 Principe du Calendrier Gabonais

Au Gabon, l'année scolaire suit un calendrier spécifique :
- **Début** : 1er septembre
- **Fin** : 30 juin

### Exemple
Pour l'année scolaire **2025-2026** :
- **Début** : 1er septembre 2025
- **Fin** : 30 juin 2026

## 🔄 Logique de Calcul Automatique

L'application calcule automatiquement l'année scolaire actuelle en fonction du mois :

### Si on est entre SEPTEMBRE et DÉCEMBRE
- Mois : 9, 10, 11, 12
- Année scolaire : **Année actuelle** - **Année suivante**
- Exemple : En octobre 2025 → Année scolaire = **2025-2026**

### Si on est entre JANVIER et AOÛT
- Mois : 1, 2, 3, 4, 5, 6, 7, 8
- Année scolaire : **Année précédente** - **Année actuelle**
- Exemple : En janvier 2026 → Année scolaire = **2025-2026**

## 💻 Utilisation dans le Code

### 1. Helper SchoolHelper

```php
use App\Helpers\SchoolHelper;

// Obtenir le nom de l'année scolaire actuelle
$yearName = SchoolHelper::getCurrentAcademicYearName();
// Retourne : "2025-2026"

// Obtenir les dates de l'année scolaire actuelle
$dates = SchoolHelper::getCurrentAcademicYearDates();
// Retourne : ['start_date' => '2025-09-01', 'end_date' => '2026-06-30']
```

### 2. Modèle AcademicYear

```php
use App\Models\AcademicYear;

// Mettre à jour l'année scolaire actuelle
$academicYear = AcademicYear::updateCurrentAcademicYear();

// Générer les années passées et futures (par défaut : 3 avant, 3 après)
AcademicYear::generateYears(3, 3);

// Obtenir l'année scolaire actuelle
$currentYear = AcademicYear::where('is_current', true)->first();
```

## 🛠️ Commandes Artisan

### Mettre à jour l'année scolaire actuelle
```bash
php artisan academic-year:update
```

### Mettre à jour ET générer les années passées/futures
```bash
php artisan academic-year:update --generate
```

## 📊 Structure de la Table `academic_years`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | Integer | ID unique |
| `name` | String | Nom (ex: "2025-2026") |
| `start_date` | Date | Date de début (1er septembre) |
| `end_date` | Date | Date de fin (30 juin) |
| `is_current` | Boolean | Est l'année actuelle ? |
| `status` | String | active/inactive |
| `description` | Text | Description de l'année |

## 🔧 Configuration Automatique

### Au démarrage de l'application
Le seeder `AcademicYearSeeder` configure automatiquement les années scolaires :

```bash
php artisan db:seed --class=AcademicYearSeeder
```

### Mise à jour automatique (Cron Job)
Pour mettre à jour automatiquement l'année scolaire chaque 1er septembre, ajoutez dans `app/Console/Kernel.php` :

```php
protected function schedule(Schedule $schedule)
{
    // Mise à jour automatique de l'année scolaire le 1er septembre à minuit
    $schedule->command('academic-year:update --generate')
             ->monthlyOn(1, '00:00')
             ->when(function () {
                 return now()->month === 9; // Uniquement en septembre
             });
}
```

## 📝 Exemples de Calcul

### Cas 1 : Nous sommes le 15 octobre 2025
- Mois actuel : 10 (octobre)
- Année actuelle : 2025
- **Calcul** : Mois >= 9 → Année scolaire = 2025-2026
- **Dates** : 01/09/2025 au 30/06/2026

### Cas 2 : Nous sommes le 20 février 2026
- Mois actuel : 2 (février)
- Année actuelle : 2026
- **Calcul** : Mois < 9 → Année scolaire = 2025-2026
- **Dates** : 01/09/2025 au 30/06/2026

### Cas 3 : Nous sommes le 5 septembre 2026
- Mois actuel : 9 (septembre)
- Année actuelle : 2026
- **Calcul** : Mois >= 9 → Année scolaire = 2026-2027
- **Dates** : 01/09/2026 au 30/06/2027

## ✅ Vérification

Pour vérifier que l'année scolaire est correctement configurée :

```bash
php artisan academic-year:update --generate
```

La commande affichera :
- L'année scolaire actuelle
- Les dates de début et fin
- La liste de toutes les années disponibles

## 🎯 Points Importants

1. **Une seule année active** : Une seule année scolaire peut être marquée comme `is_current = true` à la fois
2. **Changement automatique** : Le 1er septembre, l'année scolaire change automatiquement
3. **Période scolaire** : Septembre à Juin = 10 mois de scolarité
4. **Vacances d'été** : Juillet et Août = 2 mois de vacances

## 🔄 Migration depuis l'ancien système

Si vous aviez des années avec des dates de fin en juillet (31/07), elles ont été automatiquement corrigées pour se terminer le 30 juin.

Pour vérifier et corriger manuellement si nécessaire :

```sql
-- Voir toutes les années
SELECT * FROM academic_years ORDER BY start_date DESC;

-- Corriger une année spécifique
UPDATE academic_years 
SET end_date = '2025-06-30' 
WHERE name = '2024-2025';
```

## 📞 Support

Pour toute question sur le système d'années scolaires, consultez :
- `app/Models/AcademicYear.php` - Modèle principal
- `app/Helpers/SchoolHelper.php` - Fonctions helper
- `app/Console/Commands/UpdateAcademicYear.php` - Commande de mise à jour
- `database/seeders/AcademicYearSeeder.php` - Seeder

