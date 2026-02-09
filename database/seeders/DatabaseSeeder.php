<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\User;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRolesAndPermissions();
        $shop = $this->seedShop();
        $this->seedUsers($shop);
        $this->seedSettings();
        $this->seedPaymentMethods();
        $this->seedCategories();
        $this->seedExpenseCategories($shop);

        if (app()->environment('local', 'development', 'testing')) {
            $this->command->info('Ajout des donnees de test...');
            $this->seedSampleData($shop);
        }

        $this->command->info('Base de donnees initialisee !');
    }

    protected function seedRolesAndPermissions(): void
    {
        $permissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete', 'products.stock-entry',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'customers.view', 'customers.create', 'customers.edit',
            'resellers.view', 'resellers.create', 'resellers.edit', 'resellers.delete', 'resellers.manage-credit',
            'sales.view', 'sales.create',
            'repairs.view', 'repairs.create', 'repairs.edit', 'repairs.diagnose', 'repairs.repair', 'repairs.deliver',
            'cash-register.view', 'cash-register.open', 'cash-register.close', 'cash-register.expense',
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete', 'expenses.approve',
            'settings.view', 'settings.edit',
            'reports.view',
            'sav.view', 'sav.create', 'sav.manage',
            'maintenance.view', 'maintenance.backup', 'maintenance.cleanup',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $caissiere = Role::firstOrCreate(['name' => 'caissiere']);
        $technicien = Role::firstOrCreate(['name' => 'technicien']);

        $admin->syncPermissions(Permission::all());

        $caissiere->syncPermissions([
            'customers.view', 'customers.create', 'customers.edit',
            'resellers.view', 'products.view',
            'sales.view', 'sales.create',
            'repairs.view', 'repairs.create', 'repairs.edit', 'repairs.deliver',
            'cash-register.view', 'cash-register.open', 'cash-register.close', 'cash-register.expense',
            'expenses.view', 'expenses.create',
            'sav.view', 'sav.create',
        ]);

        $technicien->syncPermissions([
            'repairs.view', 'repairs.diagnose', 'repairs.repair', 'products.view',
        ]);
    }

    protected function seedShop(): Shop
    {
        return Shop::firstOrCreate(
            ['code' => 'BTK1'],
            ['name' => 'Boutique Principale', 'address' => 'Adresse a definir', 'phone' => '', 'email' => '', 'is_active' => true]
        );
    }

    protected function seedUsers(Shop $shop): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@egregore.com'],
            ['name' => 'Administrateur', 'password' => Hash::make('password'), 'phone' => '+225770000001', 'shop_id' => null, 'is_active' => true]
        );
        $admin->assignRole('admin');

        $caissiere = User::firstOrCreate(
            ['email' => 'caissiere@egregore.com'],
            ['name' => 'Marie Diallo', 'password' => Hash::make('password'), 'phone' => '+225770000002', 'shop_id' => $shop->id, 'is_active' => true]
        );
        $caissiere->assignRole('caissiere');

        $technicien = User::firstOrCreate(
            ['email' => 'technicien@egregore.com'],
            ['name' => 'Moussa Ndiaye', 'password' => Hash::make('password'), 'phone' => '+225770000003', 'shop_id' => $shop->id, 'is_active' => true]
        );
        $technicien->assignRole('technicien');
    }

    protected function seedSettings(): void
    {
        $defaults = Setting::getDefaults();
        foreach ($defaults as $key => $config) {
            Setting::firstOrCreate(['key' => $key], ['value' => $config['value'], 'type' => $config['type'], 'group' => $config['group']]);
        }
    }

    protected function seedPaymentMethods(): void
    {
        $methods = [
            ['name' => 'Especes', 'code' => 'cash', 'type' => 'cash', 'sort_order' => 1],
            ['name' => 'Orange Money', 'code' => 'orange_money', 'type' => 'mobile_money', 'sort_order' => 2],
            ['name' => 'Wave', 'code' => 'wave', 'type' => 'mobile_money', 'sort_order' => 3],
            ['name' => 'Moov Money', 'code' => 'moov_money', 'type' => 'mobile_money', 'sort_order' => 4],
            ['name' => 'MTN Money', 'code' => 'mtn_money', 'type' => 'mobile_money', 'sort_order' => 5],
            ['name' => 'Carte Bancaire', 'code' => 'card', 'type' => 'card', 'sort_order' => 6],
        ];
        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(['code' => $method['code']], $method);
        }
    }

    protected function seedCategories(): void
    {
        $categories = [
            ['name' => 'Telephones', 'slug' => 'telephones', 'sort_order' => 1],
            ['name' => 'Tablettes', 'slug' => 'tablettes', 'sort_order' => 2],
            ['name' => 'Accessoires', 'slug' => 'accessoires', 'sort_order' => 3],
            ['name' => 'Pieces detachees', 'slug' => 'pieces-detachees', 'sort_order' => 4],
            ['name' => 'Chargeurs', 'slug' => 'chargeurs', 'sort_order' => 5],
            ['name' => 'Coques et Protections', 'slug' => 'coques-protections', 'sort_order' => 6],
            ['name' => 'Ecouteurs et Audio', 'slug' => 'ecouteurs-audio', 'sort_order' => 7],
            ['name' => 'Montres connectees', 'slug' => 'montres-connectees', 'sort_order' => 8],
        ];
        foreach ($categories as $category) {
            Category::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }

    protected function seedExpenseCategories(Shop $shop): void
    {
        $categories = [
            ['name' => 'Loyer', 'icon' => 'bi-house', 'color' => '#dc3545', 'description' => 'Loyer mensuel', 'requires_approval' => true],
            ['name' => 'Electricite', 'icon' => 'bi-lightning', 'color' => '#ffc107', 'description' => 'Factures electricite', 'requires_approval' => false],
            ['name' => 'Eau', 'icon' => 'bi-droplet', 'color' => '#17a2b8', 'description' => 'Factures eau', 'requires_approval' => false],
            ['name' => 'Internet Telephone', 'icon' => 'bi-wifi', 'color' => '#6f42c1', 'description' => 'Abonnements', 'requires_approval' => false],
            ['name' => 'Transport', 'icon' => 'bi-car-front', 'color' => '#28a745', 'description' => 'Frais transport', 'requires_approval' => false],
            ['name' => 'Fournitures', 'icon' => 'bi-cart', 'color' => '#fd7e14', 'description' => 'Fournitures bureau', 'requires_approval' => false],
            ['name' => 'Maintenance', 'icon' => 'bi-wrench', 'color' => '#6c757d', 'description' => 'Entretien materiel', 'requires_approval' => false],
            ['name' => 'Salaires', 'icon' => 'bi-person-badge', 'color' => '#20c997', 'description' => 'Paiement salaires', 'requires_approval' => true],
            ['name' => 'Impots Taxes', 'icon' => 'bi-file-earmark-text', 'color' => '#e83e8c', 'description' => 'Impots et taxes', 'requires_approval' => true],
            ['name' => 'Divers', 'icon' => 'bi-tag', 'color' => '#6c757d', 'description' => 'Autres depenses', 'requires_approval' => false],
        ];
        foreach ($categories as $category) {
            ExpenseCategory::firstOrCreate(
                ['shop_id' => $shop->id, 'name' => $category['name']],
                ['icon' => $category['icon'], 'color' => $category['color'], 'description' => $category['description'], 'requires_approval' => $category['requires_approval'], 'is_active' => true]
            );
        }
    }

    protected function seedSampleData(Shop $shop): void
    {
        Customer::firstOrCreate(['phone' => '+225071234567'], ['first_name' => 'Kouamé', 'last_name' => 'Konan', 'shop_id' => $shop->id]);
        Reseller::firstOrCreate(['phone' => '+225076001001'], ['company_name' => 'Phone City', 'contact_name' => 'Yao Kouassi', 'credit_limit' => 500000, 'credit_allowed' => true, 'shop_id' => $shop->id]);
        
        $cat = Category::where('slug', 'telephones')->first();
        if ($cat) {
            Product::firstOrCreate(['barcode' => '1234567890123'], ['name' => 'iPhone 14 Pro Max', 'sku' => 'IP14PM', 'category_id' => $cat->id, 'purchase_price' => 750000, 'selling_price' => 850000, 'reseller_price' => 800000, 'quantity_in_stock' => 5, 'brand' => 'Apple', 'type' => 'phone', 'shop_id' => $shop->id]);
        }
    }
}
