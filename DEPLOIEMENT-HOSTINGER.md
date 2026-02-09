# Guide de Déploiement sur Hostinger

## Prérequis Hostinger

- **Plan** : Premium ou Business (PHP 8.2+ requis)
- **Base de données** : MySQL 8.0
- **Accès SSH** : Recommandé (disponible sur plans Premium+)

---

## Étape 1 : Préparer les fichiers localement

```bash
# Compiler les assets
npm run build

# Créer une archive (sans node_modules et vendor)
zip -r egregore-business.zip . -x "node_modules/*" -x "vendor/*" -x ".git/*" -x "storage/logs/*"
```

---

## Étape 2 : Configuration Hostinger

### 2.1 Créer la base de données

1. Allez dans **hPanel > Bases de données > MySQL**
2. Créez une nouvelle base de données
3. Notez :
   - Nom de la base : `u123456789_egregore`
   - Utilisateur : `u123456789_admin`
   - Mot de passe : `VotreMotDePasse`
   - Hôte : `localhost`

### 2.2 Configurer PHP

1. Allez dans **hPanel > Avancé > Configuration PHP**
2. Sélectionnez **PHP 8.2** ou supérieur
3. Activez les extensions :
   - `pdo_mysql`
   - `mbstring`
   - `openssl`
   - `tokenizer`
   - `xml`
   - `ctype`
   - `json`
   - `bcmath`
   - `fileinfo`

---

## Étape 3 : Upload des fichiers

### Option A : Via File Manager

1. Allez dans **hPanel > Fichiers > Gestionnaire de fichiers**
2. Naviguez vers `public_html`
3. Uploadez et extrayez `egregore-business.zip`

### Option B : Via SSH (recommandé)

```bash
# Connexion SSH
ssh u123456789@votre-ip -p 65002

# Navigation
cd public_html

# Upload avec scp (depuis votre PC)
scp -P 65002 egregore-business.zip u123456789@votre-ip:~/public_html/
```

---

## Étape 4 : Configuration sur le serveur

### Via SSH :

```bash
cd public_html

# Extraire
unzip egregore-business.zip

# Configurer .env
cp .env.example .env
nano .env
```

### Modifier le fichier `.env` :

```env
APP_NAME="EGREGORE BUSINESS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_egregore
DB_USERNAME=u123456789_admin
DB_PASSWORD=VotreMotDePasse

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### Installer les dépendances et configurer :

```bash
# Installer Composer (si pas disponible)
curl -sS https://getcomposer.org/installer | php
mv composer.phar composer

# Installer les dépendances
php composer install --no-dev --optimize-autoloader

# Générer la clé
php artisan key:generate

# Migrations et Seeders PRODUCTION
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder

# Lien storage
php artisan storage:link

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
chmod -R 755 storage bootstrap/cache
```

> **Note Seeders :**
> - `ProductionSeeder` : Crée uniquement les données essentielles (rôles, permissions, boutique, admin, catégories)
> - `DatabaseSeeder` : Inclut aussi des données de test (utiliser uniquement en développement)

---

## Étape 5 : Configurer le domaine

### Option A : Pointer vers /public (recommandé)

1. Allez dans **hPanel > Domaines**
2. Modifiez le **Document Root** vers : `/public_html/public`

### Option B : Utiliser le .htaccess racine

Le fichier `.htaccess` à la racine redirige automatiquement vers `/public`.

---

## Étape 6 : Activer SSL

1. Allez dans **hPanel > Sécurité > SSL**
2. Activez **Let's Encrypt** (gratuit)
3. Cochez **Forcer HTTPS**

---

## Étape 7 : Configurer les emails (optionnel)

1. Allez dans **hPanel > Emails > Comptes email**
2. Créez `noreply@votre-domaine.com`
3. Modifiez `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=noreply@votre-domaine.com
MAIL_PASSWORD=VotreMotDePasseEmail
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="EGREGORE BUSINESS"
```

---

## Étape 8 : Tâches CRON (optionnel)

Pour les notifications et nettoyages automatiques :

1. Allez dans **hPanel > Avancé > Tâches CRON**
2. Ajoutez :

```
* * * * * cd /home/u123456789/public_html && php artisan schedule:run >> /dev/null 2>&1
```

---

## Dépannage

### Erreur 500

```bash
# Vérifier les logs
tail -f storage/logs/laravel.log

# Vérifier les permissions
chmod -R 755 storage bootstrap/cache

# Vider le cache
php artisan cache:clear
php artisan config:clear
```

### Page blanche

```bash
# Activer le debug temporairement dans .env
APP_DEBUG=true

# Puis désactiver après correction
APP_DEBUG=false
php artisan config:cache
```

### Erreur de connexion BDD

1. Vérifiez les identifiants dans `.env`
2. Vérifiez que l'hôte est `localhost` (pas 127.0.0.1)
3. Testez la connexion dans phpMyAdmin

---

## Maintenance

### Mise à jour

```bash
# Mode maintenance
php artisan down

# Pull des changements (si Git)
git pull origin main

# Ou upload des nouveaux fichiers

# Mise à jour
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fin maintenance
php artisan up
```

### Sauvegarde

```bash
# Base de données
php artisan app:backup

# Les fichiers sont dans storage/backups/
```

---

## Support

- **Hostinger** : support.hostinger.com
- **Laravel** : laravel.com/docs
