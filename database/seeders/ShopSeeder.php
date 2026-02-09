<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\Repair;
use App\Models\CashRegister;
use App\Models\SavTicket;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopSeeder extends Seeder
{
    /**
     * Créer une boutique par défaut et migrer les données existantes
     */
    public function run(): void
    {
        // Créer la boutique principale si elle n'existe pas
        $shop = Shop::firstOrCreate(
            ['code' => 'BTK1'],
            [
                'name' => 'Boutique Principale',
                'address' => 'Adresse à définir',
                'phone' => '',
                'email' => '',
                'is_active' => true,
            ]
        );

        $this->command->info("Boutique créée: {$shop->name} ({$shop->code})");

        // Migrer les utilisateurs sans boutique (sauf admins)
        $usersUpdated = User::whereNull('shop_id')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin');
            })
            ->update(['shop_id' => $shop->id]);

        $this->command->info("Utilisateurs migrés: {$usersUpdated}");

        // Migrer les produits sans boutique
        $productsUpdated = Product::whereNull('shop_id')->update(['shop_id' => $shop->id]);
        $this->command->info("Produits migrés: {$productsUpdated}");

        // Migrer les clients sans boutique
        $customersUpdated = Customer::whereNull('shop_id')->update(['shop_id' => $shop->id]);
        $this->command->info("Clients migrés: {$customersUpdated}");

        // Migrer les revendeurs sans boutique
        $resellersUpdated = Reseller::whereNull('shop_id')->update(['shop_id' => $shop->id]);
        $this->command->info("Revendeurs migrés: {$resellersUpdated}");

        // Migrer les ventes sans boutique
        $salesUpdated = DB::table('sales')->whereNull('shop_id')->update(['shop_id' => $shop->id]);
        $this->command->info("Ventes migrées: {$salesUpdated}");

        // Migrer les réparations sans boutique
        $repairsUpdated = DB::table('repairs')->whereNull('shop_id')->update(['shop_id' => $shop->id]);
        $this->command->info("Réparations migrées: {$repairsUpdated}");

        // Migrer les caisses sans boutique
        $cashRegistersUpdated = DB::table('cash_registers')->whereNull('shop_id')->update(['shop_id' => $shop->id]);
        $this->command->info("Caisses migrées: {$cashRegistersUpdated}");

        // Migrer les tickets SAV sans boutique
        $savTicketsUpdated = DB::table('sav_tickets')->whereNull('shop_id')->update(['shop_id' => $shop->id]);
        $this->command->info("Tickets SAV migrés: {$savTicketsUpdated}");

        // Migrer les mouvements de stock sans boutique
        $stockMovementsUpdated = DB::table('stock_movements')->whereNull('shop_id')->update(['shop_id' => $shop->id]);
        $this->command->info("Mouvements de stock migrés: {$stockMovementsUpdated}");

        $this->command->info("✅ Migration multi-boutiques terminée !");
    }
}
