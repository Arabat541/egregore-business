<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shops = Shop::all();

        $defaultCategories = [
            [
                'name' => 'Loyer',
                'icon' => 'bi-house',
                'color' => '#dc3545',
                'description' => 'Loyer mensuel du local',
                'requires_approval' => true,
            ],
            [
                'name' => 'Électricité',
                'icon' => 'bi-lightning',
                'color' => '#ffc107',
                'description' => 'Factures d\'électricité',
                'requires_approval' => false,
            ],
            [
                'name' => 'Eau',
                'icon' => 'bi-droplet',
                'color' => '#17a2b8',
                'description' => 'Factures d\'eau',
                'requires_approval' => false,
            ],
            [
                'name' => 'Internet / Téléphone',
                'icon' => 'bi-wifi',
                'color' => '#6f42c1',
                'description' => 'Abonnements internet et téléphone',
                'requires_approval' => false,
            ],
            [
                'name' => 'Transport',
                'icon' => 'bi-car-front',
                'color' => '#28a745',
                'description' => 'Frais de transport et déplacement',
                'requires_approval' => false,
            ],
            [
                'name' => 'Fournitures',
                'icon' => 'bi-cart',
                'color' => '#fd7e14',
                'description' => 'Fournitures de bureau et consommables',
                'requires_approval' => false,
            ],
            [
                'name' => 'Maintenance',
                'icon' => 'bi-wrench',
                'color' => '#6c757d',
                'description' => 'Réparations et entretien du matériel',
                'requires_approval' => false,
            ],
            [
                'name' => 'Salaires',
                'icon' => 'bi-person-badge',
                'color' => '#20c997',
                'description' => 'Paiement des salaires',
                'requires_approval' => true,
            ],
            [
                'name' => 'Impôts / Taxes',
                'icon' => 'bi-file-earmark-text',
                'color' => '#e83e8c',
                'description' => 'Impôts et taxes diverses',
                'requires_approval' => true,
            ],
            [
                'name' => 'Divers',
                'icon' => 'bi-tag',
                'color' => '#6c757d',
                'description' => 'Autres dépenses non catégorisées',
                'requires_approval' => false,
            ],
        ];

        foreach ($shops as $shop) {
            foreach ($defaultCategories as $category) {
                ExpenseCategory::firstOrCreate(
                    [
                        'shop_id' => $shop->id,
                        'name' => $category['name'],
                    ],
                    [
                        'icon' => $category['icon'],
                        'color' => $category['color'],
                        'description' => $category['description'],
                        'requires_approval' => $category['requires_approval'],
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('Catégories de dépenses créées pour ' . $shops->count() . ' boutique(s).');
    }
}
