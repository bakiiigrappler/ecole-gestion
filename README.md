# Egesco - Système de Gestion Scolaire

## Description
Egesco est un système complet de gestion scolaire développé avec Laravel, offrant une solution moderne et intuitive pour la gestion des établissements éducatifs.

## Fonctionnalités Principales

### 🎓 Gestion Académique
- **Gestion des étudiants** : Inscription, réinscription, suivi des performances
- **Gestion des classes** : Organisation par niveaux et cycles
- **Gestion des enseignants** : Attribution des matières et emplois du temps
- **Système de notes** : Saisie et suivi des évaluations

### 📊 Présence et Assiduité
- **Pointage quotidien** : Suivi de présence par créneaux horaires
- **Statistiques de présence** : Analyses et rapports détaillés
- **Gestion des absences** : Justification et suivi

### 💰 Gestion Financière
- **Système de frais hiérarchique** : Niveau → Classe → Étudiant
- **Gestion des paiements** : Suivi des transactions et reçus
- **Rapports financiers** : Analyses des revenus et statistiques

### 📈 Tableaux de Bord et Statistiques
- **Dashboard principal** : Vue d'ensemble des performances
- **Statistiques avancées** : Analyses détaillées avec graphiques
- **Rapports personnalisés** : Export et visualisation des données

## Technologies Utilisées

- **Backend** : Laravel 11.x
- **Frontend** : Bootstrap 5, Chart.js
- **Base de données** : MySQL
- **Authentification** : Laravel Sanctum
- **API** : RESTful API avec documentation

## Installation

### Prérequis
- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js & NPM

### Étapes d'installation

1. **Cloner le repository**
```bash
git clone https://github.com/bakii-hanma/egestco.git
cd egestco
```

2. **Installer les dépendances**
```bash
composer install
npm install
```

3. **Configuration de l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configuration de la base de données**
Modifiez le fichier `.env` avec vos paramètres de base de données :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=egestco
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Migration et seeding**
```bash
php artisan migrate
php artisan db:seed
```

6. **Compilation des assets**
```bash
npm run build
```

7. **Démarrage du serveur**
```bash
php artisan serve
```

## Configuration Serveur

### Apache (.htaccess)
Le projet inclut un fichier `.htaccess` optimisé pour Apache avec :
- Configuration PHP pour éviter les timeouts
- Gestion des headers d'autorisation
- Redirection vers le contrôleur frontal

### Base de données
- **MySQL** : Base de données principale
- **Migrations** : Structure complète des tables
- **Seeders** : Données de test et comptes administrateurs

## Comptes par Défaut

### Super Administrateur
- **Email** : superadmin@egestco.com
- **Mot de passe** : superadmin123
- **Rôle** : Accès complet au système

### Administrateur
- **Email** : admin@egestco.com
- **Mot de passe** : admin123
- **Rôle** : Gestion quotidienne

## Structure du Projet

```
egestco/
├── app/
│   ├── Http/Controllers/     # Contrôleurs
│   ├── Models/              # Modèles Eloquent
│   ├── Services/            # Services métier
│   └── ...
├── database/
│   ├── migrations/          # Migrations
│   ├── seeders/            # Seeders
│   └── ...
├── resources/
│   ├── views/              # Vues Blade
│   ├── css/                # Styles CSS
│   └── js/                 # Scripts JavaScript
├── routes/
│   ├── web.php             # Routes web
│   └── api.php             # Routes API
└── ...
```

## API Documentation

Le système expose une API RESTful pour :
- Gestion des paiements
- Récupération des emplois du temps
- Statistiques et rapports

### Endpoints principaux
- `GET /api/v1/payments` - Liste des paiements
- `POST /api/v1/payments` - Créer un paiement
- `GET /api/schedules/classes/by-cycle` - Classes par cycle

## Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## Support

Pour toute question ou problème :
- Créer une issue sur GitHub
- Contacter l'équipe de développement

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## Changelog

### Version 1.0.0
- Système de gestion scolaire complet
- Interface moderne et responsive
- API RESTful
- Système de paiements intégré
- Statistiques avancées
- Gestion des présences par créneaux

---

**Egesco** - Simplifiant la gestion scolaire moderne 🎓