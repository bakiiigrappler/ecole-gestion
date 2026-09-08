# Guide d'Installation - Egesco

## 🚀 Installation sur Serveur

### Prérequis Système

#### Serveur Web
- **Apache 2.4+** ou **Nginx 1.18+**
- **PHP 8.2+** avec les extensions suivantes :
  - BCMath PHP Extension
  - Ctype PHP Extension
  - cURL PHP Extension
  - DOM PHP Extension
  - Fileinfo PHP Extension
  - JSON PHP Extension
  - Mbstring PHP Extension
  - OpenSSL PHP Extension
  - PCRE PHP Extension
  - PDO PHP Extension
  - Tokenizer PHP Extension
  - XML PHP Extension

#### Base de Données
- **MySQL 8.0+** ou **MariaDB 10.4+**

#### Outils
- **Composer** (gestionnaire de dépendances PHP)
- **Node.js 18+** et **NPM** (pour les assets frontend)

### 📋 Étapes d'Installation

#### 1. Cloner le Repository

```bash
git clone https://github.com/bakii-hanma/egestco.git
cd egestco
```

#### 2. Installation des Dépendances PHP

```bash
composer install --optimize-autoloader --no-dev
```

#### 3. Installation des Dépendances Frontend

```bash
npm install
npm run build
```

#### 4. Configuration de l'Environnement

```bash
cp .env.example .env
php artisan key:generate
```

#### 5. Configuration de la Base de Données

Modifiez le fichier `.env` avec vos paramètres :

```env
APP_NAME="Egesco"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=false
APP_URL=https://votre-domaine.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=egestco
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe

# Configuration Mail (optionnel)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre_email@gmail.com
MAIL_PASSWORD=votre_mot_de_passe_app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### 6. Création de la Base de Données

```sql
CREATE DATABASE egestco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 7. Migration et Seeding

```bash
php artisan migrate --force
php artisan db:seed --force
```

#### 8. Configuration des Permissions

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

#### 9. Optimisation pour la Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 🔧 Configuration Apache

#### Virtual Host

```apache
<VirtualHost *:80>
    ServerName votre-domaine.com
    ServerAlias www.votre-domaine.com
    DocumentRoot /var/www/egestco/public
    
    <Directory /var/www/egestco/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/egestco_error.log
    CustomLog ${APACHE_LOG_DIR}/egestco_access.log combined
</VirtualHost>
```

#### SSL (Recommandé)

```apache
<VirtualHost *:443>
    ServerName votre-domaine.com
    ServerAlias www.votre-domaine.com
    DocumentRoot /var/www/egestco/public
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /var/www/egestco/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/egestco_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/egestco_ssl_access.log combined
</VirtualHost>
```

### 🔧 Configuration Nginx

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name votre-domaine.com www.votre-domaine.com;
    root /var/www/egestco/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 🔐 Comptes Administrateurs par Défaut

Après l'installation, vous pouvez vous connecter avec :

#### Super Administrateur
- **Email** : `superadmin@egestco.com`
- **Mot de passe** : `superadmin123`
- **Accès** : Toutes les fonctionnalités du système

#### Administrateur
- **Email** : `admin@egestco.com`
- **Mot de passe** : `admin123`
- **Accès** : Gestion quotidienne (sans accès aux paramètres système)

> ⚠️ **Important** : Changez ces mots de passe après la première connexion !

### 📊 Configuration de la Base de Données

#### Structure des Tables Principales

- `users` - Utilisateurs du système
- `students` - Étudiants
- `teachers` - Enseignants
- `school_classes` - Classes
- `levels` - Niveaux d'enseignement
- `subjects` - Matières
- `enrollments` - Inscriptions
- `attendances` - Présences
- `grades` - Notes
- `payments` - Paiements
- `level_fees` - Frais par niveau
- `class_fees` - Frais par classe
- `enrollment_fees` - Frais d'inscription

### 🔄 Maintenance et Mises à Jour

#### Mise à Jour du Code

```bash
git pull origin main
composer install --optimize-autoloader --no-dev
npm run build
php artisan migrate --force
php artisan optimize
```

#### Sauvegarde de la Base de Données

```bash
mysqldump -u username -p egestco > backup_$(date +%Y%m%d_%H%M%S).sql
```

#### Nettoyage des Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

### 🚨 Dépannage

#### Problèmes Courants

1. **Erreur 500** : Vérifiez les permissions et les logs
2. **Base de données** : Vérifiez la connexion dans `.env`
3. **Assets manquants** : Exécutez `npm run build`
4. **Permissions** : Vérifiez les permissions sur `storage` et `bootstrap/cache`

#### Logs

```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Logs Apache
tail -f /var/log/apache2/error.log

# Logs Nginx
tail -f /var/log/nginx/error.log
```

### 📈 Optimisation des Performances

#### Cache Redis (Optionnel)

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### Queue Workers (Optionnel)

```bash
php artisan queue:work --daemon
```

### 🔒 Sécurité

#### Recommandations

1. **HTTPS** : Utilisez toujours HTTPS en production
2. **Firewall** : Configurez un firewall approprié
3. **Mots de passe** : Changez tous les mots de passe par défaut
4. **Permissions** : Limitez les permissions des fichiers
5. **Backups** : Effectuez des sauvegardes régulières

### 📞 Support

En cas de problème :

1. Vérifiez les logs d'erreur
2. Consultez la documentation Laravel
3. Créez une issue sur GitHub
4. Contactez l'équipe de support

---

**Egesco** - Installation réussie ! 🎉
