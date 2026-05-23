<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder pour la production - Données essentielles uniquement
 * 
 * Crée uniquement:
 * - Rôles et permissions
 * - Compte administrateur
 * - Paramètres système
 * - Méthodes de paiement
 * - Catégories de produits
 * 
 * L'admin créera ensuite:
 * - Les boutiques
 * - Les comptes employés
 * - Les catégories de dépenses
 * 
 * Usage: php artisan db:seed --class=ProductionSeeder
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Initialisation de la base de données pour la PRODUCTION...');
        $this->command->newLine();

        // 1. Rôles et permissions
        $this->seedRolesAndPermissions();
        $this->command->info('✅ Rôles et permissions créés');

        // 2. Compte administrateur (sans boutique - il créera les boutiques lui-même)
        $admin = $this->seedAdminUser();
        $this->command->info("✅ Administrateur créé: {$admin->email}");

        // 3. Paramètres système
        $this->seedSettings();
        $this->command->info('✅ Paramètres système configurés');

        // 4. Méthodes de paiement
        $this->seedPaymentMethods();
        $this->command->info('✅ Méthodes de paiement créées');

        // 5. Catégories de produits
        $this->seedCategories();
        $this->command->info('✅ Catégories de produits créées');

        $this->command->newLine();
        $this->command->info('🎉 Base de données initialisée avec succès !');
        $this->command->newLine();
        $this->command->warn('📧 Connexion Admin:');
        $this->command->line("   Email: {$admin->email}");
        $this->command->line("   Mot de passe: Celui configuré dans .env (ADMIN_PASSWORD)");
        $this->command->newLine();
        $this->command->warn('📋 Prochaines étapes:');
        $this->command->line("   1. Connectez-vous avec le compte admin");
        $this->command->line("   2. Créez votre première boutique");
        $this->command->line("   3. Créez les comptes employés (caissières, techniciens)");
        $this->command->line("   4. Configurez les catégories de dépenses pour chaque boutique");
    }

    protected function seedRolesAndPermissions(): void
    {
        // Permissions
        $permissions = [
            // Users
            'users.view', 'users.create', 'users.edit', 'users.delete',
            // Products
            'products.view', 'products.create', 'products.edit', 'products.delete', 'products.stock-entry',
            // Categories
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            // Customers
            'customers.view', 'customers.create', 'customers.edit',
            // Resellers
            'resellers.view', 'resellers.create', 'resellers.edit', 'resellers.delete', 'resellers.manage-credit',
            // Sales
            'sales.view', 'sales.create',
            // Repairs
            'repairs.view', 'repairs.create', 'repairs.edit', 'repairs.diagnose', 'repairs.repair', 'repairs.deliver',
            // Cash Register
            'cash-register.view', 'cash-register.open', 'cash-register.close', 'cash-register.expense',
            // Expenses
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete', 'expenses.approve',
            // Settings
            'settings.view', 'settings.edit',
            // Reports
            'reports.view',
            // SAV
            'sav.view', 'sav.create', 'sav.manage',
            // Maintenance
            'maintenance.view', 'maintenance.backup', 'maintenance.cleanup',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Rôles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $caissiere = Role::firstOrCreate(['name' => 'caissiere']);
        $technicien = Role::firstOrCreate(['name' => 'technicien']);

        // Admin - Toutes les permissions
        $admin->syncPermissions(Permission::all());

        // Caissière - Opérations quotidiennes
        $caissiere->syncPermissions([
            'customers.view', 'customers.create', 'customers.edit',
            'resellers.view',
            'products.view',
            'sales.view', 'sales.create',
            'repairs.view', 'repairs.create', 'repairs.edit', 'repairs.deliver',
            'cash-register.view', 'cash-register.open', 'cash-register.close', 'cash-register.expense',
            'expenses.view', 'expenses.create',
            'sav.view', 'sav.create',
        ]);

        // Technicien - Réparations uniquement
        $technicien->syncPermissions([
            'repairs.view', 'repairs.diagnose', 'repairs.repair',
            'products.view',
        ]);
    }

    protected function seedAdminUser(): User
    {
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@egregore-business.com')],
            [
                'name' => env('ADMIN_NAME', 'Administrateur'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeM0i!2026')),
                'phone' => env('ADMIN_PHONE', ''),
                'shop_id' => null, // Admin n'est lié à aucune boutique - accès global
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');
        
        return $admin;
    }

    protected function seedSettings(): void
    {
        $defaults = Setting::getDefaults();

        foreach ($defaults as $key => $config) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'group' => $config['group'],
                ]
            );
        }
    }

    protected function seedPaymentMethods(): void
    {
        $methods = [
            ['name' => 'Espèces', 'code' => 'cash', 'type' => 'cash', 'sort_order' => 1],
            ['name' => 'Orange Money', 'code' => 'orange_money', 'type' => 'mobile_money', 'sort_order' => 2],
            ['name' => 'Wave', 'code' => 'wave', 'type' => 'mobile_money', 'sort_order' => 3],
            ['name' => 'MTN Money', 'code' => 'mtn_money', 'type' => 'mobile_money', 'sort_order' => 4],
            ['name' => 'Moov Money', 'code' => 'moov_money', 'type' => 'mobile_money', 'sort_order' => 5],
            ['name' => 'Carte Bancaire', 'code' => 'card', 'type' => 'card', 'sort_order' => 6],
            ['name' => 'Virement Bancaire', 'code' => 'bank_transfer', 'type' => 'bank', 'sort_order' => 7],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }

    protected function seedCategories(): void
    {
        $categories = [
            ['name' => 'Téléphones', 'slug' => 'telephones', 'sort_order' => 1],
            ['name' => 'Tablettes', 'slug' => 'tablettes', 'sort_order' => 2],
            ['name' => 'Accessoires', 'slug' => 'accessoires', 'sort_order' => 3],
            ['name' => 'Pièces détachées', 'slug' => 'pieces-detachees', 'sort_order' => 4],
            ['name' => 'Chargeurs', 'slug' => 'chargeurs', 'sort_order' => 5],
            ['name' => 'Coques & Protections', 'slug' => 'coques-protections', 'sort_order' => 6],
            ['name' => 'Écouteurs & Audio', 'slug' => 'ecouteurs-audio', 'sort_order' => 7],
            ['name' => 'Montres connectées', 'slug' => 'montres-connectees', 'sort_order' => 8],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
