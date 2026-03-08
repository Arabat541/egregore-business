# Déploiement EGREGORE BUSINESS sur Hostinger

## Prérequis Hostinger

- **Plan recommandé** : Business Web Hosting ou Premium (PHP 8.2+, MySQL, SSH)
- **Domaine** configuré sur Hostinger

---

## ÉTAPE 1 : Préparer l'application en local

### 1.1 Optimiser pour production

```powershell
# Dans le dossier du projet
cd "c:\Users\LEZOGO\Desktop\CRM BOOT\CRM BOOT"

# Installer les dépendances de production
composer install --optimize-autoloader --no-dev

# Compiler les assets
npm run build
```

### 1.2 Configurer .env pour production

Créez un fichier `.env.production` avec ces paramètres :

```env
APP_NAME="EGREGORE BUSINESS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://egregore-business.org

# Base de données Hostinger (voir hPanel > Databases)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_egregore
DB_USERNAME=u123456789_user
DB_PASSWORD=VotreMotDePasse

# Sessions et cache
SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# Admin (optionnel - valeurs par défaut si non définies)
ADMIN_EMAIL=admin@egregore-business.org
ADMIN_NAME=Administrateur
ADMIN_PASSWORD=VotreMotDePasseSecurise

# Mail (optionnel)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=contact@egregore-business.org
MAIL_PASSWORD=MotDePasseEmail
```

---

## ÉTAPE 2 : Configurer Hostinger

### 2.1 Créer la base de données

1. Connectez-vous au **hPanel** Hostinger
2. Allez dans **Databases → MySQL Databases**
3. Créez une base de données (notez le nom, ex: `u123456789_egregore`)
4. Créez un utilisateur avec tous les privilèges
5. Notez les identifiants

### 2.2 Activer SSH

1. Dans hPanel → **Advanced → SSH Access**
2. Activez SSH et notez les identifiants
3. Générez ou uploadez votre clé SSH (recommandé)

---

## ÉTAPE 3 : Uploader les fichiers

### Option A : Via File Manager (plus simple)

1. **Compressez le projet** en ZIP (sans `node_modules` ni `.git`)
2. Dans hPanel → **File Manager**
3. Naviguez vers `public_html`
4. Uploadez et décompressez le ZIP
5. **Déplacez le contenu de `public/`** vers `public_html/`
6. Les autres dossiers restent dans un dossier parent (ex: `app_laravel/`)

### Option B : Via SSH/SFTP (recommandé)

```bash
# Connexion SSH
ssh u123456789@votre-ip -p 65002

# Ou avec FileZilla/WinSCP
Host: votre-ip
Port: 65002
User: u123456789
```

### Structure finale sur Hostinger :

```
/home/u123456789/
├── domains/
│   └── egregore-business.org/
│       ├── public_html/          ← Contenu de /public
│       │   ├── index.php         ← MODIFIÉ (voir ci-dessous)
│       │   ├── build/
│       │   ├── .htaccess
│       │   └── ...
│       └── app_laravel/          ← Le reste de l'application
│           ├── app/
│           ├── bootstrap/
│           ├── config/
│           ├── database/
│           ├── resources/
│           ├── routes/
│           ├── storage/
│           ├── vendor/
│           ├── .env
│           └── ...
```

---

## ÉTAPE 4 : Configurer index.php

Modifiez `public_html/index.php` pour pointer vers le bon dossier :

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Chemin vers l'application Laravel (un niveau au-dessus)
$appPath = dirname(__DIR__) . '/app_laravel';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appPath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once $appPath.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
```

---

## ÉTAPE 5 : Configuration via SSH

Connectez-vous en SSH et exécutez :

```bash
# Aller dans le dossier de l'application
cd ~/domains/egregore-business.org/app_laravel

# Permissions des dossiers storage et cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Copier .env.production vers .env
cp .env.production .env

# Générer la clé d'application
php artisan key:generate

# Créer le lien symbolique storage (depuis public_html)
cd ~/domains/egregore-business.org/public_html
ln -s ../app_laravel/storage/app/public storage

# Exécuter les migrations
cd ~/domains/egregore-business.org/app_laravel
php artisan migrate --force

# Créer les tables de session si nécessaire
php artisan session:table
php artisan migrate --force

# Optimiser l'application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Initialiser les données (admin, rôles, permissions, etc.)
php artisan db:seed --class=ProductionSeeder --force
```

---

## ÉTAPE 6 : Configurer .htaccess

Vérifiez que `public_html/.htaccess` contient :

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## ÉTAPE 7 : Forcer HTTPS

Ajoutez en haut de `.htaccess` dans `public_html` :

```apache
# Forcer HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Résolution de problèmes

### Erreur 500
```bash
# Vérifier les logs
tail -f ~/domains/egregore-business.org/app_laravel/storage/logs/laravel.log

# Vérifier les permissions
chmod -R 775 storage bootstrap/cache
```

### Page blanche
```bash
# Vider le cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Erreur de base de données
- Vérifiez les identifiants dans `.env`
- Testez la connexion : `php artisan tinker` puis `DB::connection()->getPdo();`

### Assets non chargés (CSS/JS)
- Vérifiez que `APP_URL` dans `.env` est correct
- Vérifiez que le dossier `build/` est dans `public_html/`

---

## Mises à jour futures

```bash
# Via SSH
cd ~/domains/egregore-business.org/app_laravel

# Activer le mode maintenance
php artisan down

# Télécharger les nouveaux fichiers (FTP ou git pull)

# Mettre à jour les dépendances
composer install --optimize-autoloader --no-dev

# Exécuter les migrations
php artisan migrate --force

# Vider les caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Désactiver le mode maintenance
php artisan up
```

---

## Informations de connexion admin par défaut

- **Email** : admin@egregore-business.com (ou ADMIN_EMAIL dans .env)
- **Mot de passe** : ChangeM0i!2026 (ou ADMIN_PASSWORD dans .env)

⚠️ **CHANGEZ LE MOT DE PASSE IMMÉDIATEMENT APRÈS LA PREMIÈRE CONNEXION !**

### Variables .env optionnelles pour l'admin :
```env
ADMIN_EMAIL=admin@egregore-business.org
ADMIN_NAME=Administrateur
ADMIN_PASSWORD=VotreMotDePasseSecurise
ADMIN_PHONE=+241XXXXXXXX
```
