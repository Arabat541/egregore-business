# Script de préparation pour déploiement Hostinger
# À exécuter dans PowerShell depuis le dossier du projet

Write-Host "=== Préparation du déploiement EGREGORE BUSINESS ===" -ForegroundColor Cyan

# Vérifier le dossier courant
$projectPath = Get-Location
Write-Host "Dossier projet: $projectPath" -ForegroundColor Yellow

# 1. Installer les dépendances de production
Write-Host "`n[1/4] Installation des dépendances Composer (production)..." -ForegroundColor Green
composer install --optimize-autoloader --no-dev

# 2. Compiler les assets
Write-Host "`n[2/4] Compilation des assets (npm run build)..." -ForegroundColor Green
npm run build

# 3. Créer le dossier de déploiement
$deployPath = "$projectPath\deploy_hostinger"
if (Test-Path $deployPath) {
    Remove-Item -Recurse -Force $deployPath
}
New-Item -ItemType Directory -Path $deployPath | Out-Null
New-Item -ItemType Directory -Path "$deployPath\app_laravel" | Out-Null
New-Item -ItemType Directory -Path "$deployPath\public_html" | Out-Null

Write-Host "`n[3/4] Copie des fichiers..." -ForegroundColor Green

# Copier les fichiers de l'application Laravel
$laravelFolders = @("app", "bootstrap", "config", "database", "lang", "resources", "routes", "storage", "vendor")
foreach ($folder in $laravelFolders) {
    if (Test-Path "$projectPath\$folder") {
        Copy-Item -Recurse "$projectPath\$folder" "$deployPath\app_laravel\"
        Write-Host "  Copié: $folder/" -ForegroundColor Gray
    }
}

# Copier les fichiers racine
$rootFiles = @("artisan", "composer.json", "composer.lock", ".env.example")
foreach ($file in $rootFiles) {
    if (Test-Path "$projectPath\$file") {
        Copy-Item "$projectPath\$file" "$deployPath\app_laravel\"
        Write-Host "  Copié: $file" -ForegroundColor Gray
    }
}

# Copier le contenu de public vers public_html
Copy-Item -Recurse "$projectPath\public\*" "$deployPath\public_html\"
Write-Host "  Copié: public/ -> public_html/" -ForegroundColor Gray

# 4. Modifier index.php pour Hostinger
Write-Host "`n[4/4] Configuration de index.php pour Hostinger..." -ForegroundColor Green

$indexContent = @'
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
'@

Set-Content -Path "$deployPath\public_html\index.php" -Value $indexContent

# Créer .htaccess avec HTTPS forcé
$htaccessContent = @'
# Forcer HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

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
'@

Set-Content -Path "$deployPath\public_html\.htaccess" -Value $htaccessContent

# Créer un fichier .env.example pour production
$envProdContent = @'
APP_NAME="EGREGORE BUSINESS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://egregore-business.org

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_egregore
DB_USERNAME=u123456789_user
DB_PASSWORD=VotreMotDePasse

SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# Admin credentials
ADMIN_EMAIL=admin@egregore-business.org
ADMIN_NAME=Administrateur
ADMIN_PASSWORD=VotreMotDePasseSecurise
'@

Set-Content -Path "$deployPath\app_laravel\.env.production" -Value $envProdContent

# Nettoyer les fichiers inutiles
Remove-Item -Recurse -Force "$deployPath\app_laravel\storage\logs\*" -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force "$deployPath\app_laravel\storage\framework\cache\data\*" -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force "$deployPath\app_laravel\storage\framework\sessions\*" -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force "$deployPath\app_laravel\storage\framework\views\*" -ErrorAction SilentlyContinue

# Créer les fichiers .gitkeep nécessaires
New-Item -ItemType File -Path "$deployPath\app_laravel\storage\logs\.gitkeep" -Force | Out-Null
New-Item -ItemType File -Path "$deployPath\app_laravel\storage\framework\cache\data\.gitkeep" -Force | Out-Null
New-Item -ItemType File -Path "$deployPath\app_laravel\storage\framework\sessions\.gitkeep" -Force | Out-Null
New-Item -ItemType File -Path "$deployPath\app_laravel\storage\framework\views\.gitkeep" -Force | Out-Null

Write-Host "`n=== Préparation terminée ! ===" -ForegroundColor Cyan
Write-Host "Dossier de déploiement: $deployPath" -ForegroundColor Yellow
Write-Host "`nProchaines étapes:" -ForegroundColor White
Write-Host "1. Créez la base de données sur Hostinger (hPanel > Databases)"
Write-Host "2. Modifiez app_laravel\.env.production avec vos identifiants DB"
Write-Host "3. Uploadez le contenu de 'deploy_hostinger' sur Hostinger"
Write-Host "   - public_html/ -> public_html/"
Write-Host "   - app_laravel/ -> app_laravel/ (même niveau que public_html)"
Write-Host "4. Via SSH, exécutez les commandes de configuration (voir DEPLOIEMENT-HOSTINGER.md)"
Write-Host ""
