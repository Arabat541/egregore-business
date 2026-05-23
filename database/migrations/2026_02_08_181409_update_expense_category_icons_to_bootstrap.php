<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mapping Font Awesome -> Bootstrap Icons
     */
    private array $iconMapping = [
        'fa-tag' => 'bi-tag',
        'fa-home' => 'bi-house',
        'fa-bolt' => 'bi-lightning',
        'fa-car' => 'bi-car-front',
        'fa-wrench' => 'bi-wrench',
        'fa-phone' => 'bi-telephone',
        'fa-shopping-cart' => 'bi-cart',
        'fa-user-tie' => 'bi-person-badge',
        'fa-building' => 'bi-building',
        'fa-file-invoice' => 'bi-file-earmark-text',
        'fa-truck' => 'bi-truck',
        'fa-tools' => 'bi-tools',
        'fa-utensils' => 'bi-cup-hot',
        'fa-gas-pump' => 'bi-fuel-pump',
        'fa-wifi' => 'bi-wifi',
        'fa-water' => 'bi-droplet',
        'fa-plug' => 'bi-plug',
        'fa-money-bill' => 'bi-cash',
        'fa-receipt' => 'bi-receipt',
        'fa-file-alt' => 'bi-file-text',
        'fa-cog' => 'bi-gear',
        'fa-users' => 'bi-people',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convertir les icônes Font Awesome en Bootstrap Icons
        foreach ($this->iconMapping as $faIcon => $biIcon) {
            DB::table('expense_categories')
                ->where('icon', $faIcon)
                ->update(['icon' => $biIcon]);
        }

        // Mettre une icône par défaut pour celles qui n'ont pas de mapping
        DB::table('expense_categories')
            ->where('icon', 'like', 'fa-%')
            ->update(['icon' => 'bi-tag']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convertir les icônes Bootstrap Icons en Font Awesome
        foreach ($this->iconMapping as $faIcon => $biIcon) {
            DB::table('expense_categories')
                ->where('icon', $biIcon)
                ->update(['icon' => $faIcon]);
        }
    }
};
