# EGREGORE BUSINESS

**CRM pour boutique de réparation et vente de téléphones**

---

## Présentation

Application de gestion complète pour les boutiques de réparation et vente de téléphones et accessoires.

### Fonctionnalités

- Multi-boutiques
- Multi-rôles (Admin, Caissière, Technicien)
- Ventes (particuliers et revendeurs)
- Réparations avec suivi
- Service Après-Vente (S.A.V)
- Gestion de caisse
- Gestion des dépenses
- Rapports et analyses

---

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

---

## Comptes par défaut

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@egregore.com | password |
| Caissière | caisse@egregore.com | password |
| Technicien | tech@egregore.com | password |

---

## Stack Technique

- Laravel 11
- PHP 8.2+
- MySQL 8.0
- Bootstrap 5

---

## Licence

Propriétaire © 2026 EGREGORE BUSINESS
