# CRM Phone Repair & Sales Shop - Instructions

## Project Overview
CRM/Application de gestion pour une boutique de réparation et vente de téléphones et accessoires.

## Tech Stack
- **Backend**: PHP Laravel 11
- **Database**: MySQL
- **Architecture**: MVC (Laravel)
- **Authentication**: Laravel Auth avec Spatie Permission (roles & permissions)

## Roles
1. **Admin** - Gestion système, paramétrage, stock (lecture seule sur opérations)
2. **Caissière** - Opérations quotidiennes (ventes, caisse, réparations)
3. **Technicien** - Module réparations uniquement

## Modules
- [x] Authentication & Roles
- [x] Users Management
- [x] Customers (Particuliers)
- [x] Resellers (Revendeurs)
- [x] Products & Stock
- [x] Sales (Ventes)
- [x] Repairs (Réparations)
- [x] Cash Register (Caisse)
- [x] Dashboards

## Setup Progress
- [x] Project Requirements Clarified
- [x] Project Scaffolded
- [x] Customize the Project
- [x] Install Required Extensions
- [x] Compile the Project
- [x] Launch the Project
- [x] Documentation Complete

## Development Guidelines
- Follow Laravel conventions
- Use French for business logic comments
- Use Eloquent ORM for database operations
- Implement middleware for role-based access control
