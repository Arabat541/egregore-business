<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Repair;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests de la recherche globale (Ctrl+K) — GET /search?q=
 */
class GlobalSearchTest extends TestCase
{

    private function makeUserWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);

        // Non-admin users must have a shop_id (ShopScope middleware enforces this).
        $shopId = null;
        if ($role !== 'admin') {
            $shop   = \App\Models\Shop::firstOrCreate(['code' => 'TST1'], ['name' => 'Test Shop', 'is_active' => true]);
            $shopId = $shop->id;
        }

        $user = User::factory()->create(['is_active' => true, 'shop_id' => $shopId]);
        $user->assignRole($role);
        return $user;
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->getJson(route('global.search', ['q' => 'test']));
        $response->assertStatus(401);
    }

    public function test_query_shorter_than_two_chars_returns_empty(): void
    {
        $user = $this->makeUserWithRole('admin');

        $response = $this->actingAs($user)
            ->getJson(route('global.search', ['q' => 'a']));

        $response->assertOk()
                 ->assertJson(['results' => []]);
    }

    public function test_admin_can_search_products(): void
    {
        $user    = $this->makeUserWithRole('admin');
        $product = Product::factory()->create(['name' => 'iPhone 15 Pro', 'is_active' => true]);

        $response = $this->actingAs($user)
            ->getJson(route('global.search', ['q' => 'iPhone']));

        $response->assertOk()
                 ->assertJsonFragment(['label' => $product->name, 'group' => 'Produits']);
    }

    public function test_admin_can_search_customers(): void
    {
        $user     = $this->makeUserWithRole('admin');
        $customer = Customer::factory()->create([
            'first_name' => 'Konan',
            'last_name'  => 'Yves',
            'is_active'  => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('global.search', ['q' => 'Konan']));

        $response->assertOk()
                 ->assertJsonFragment(['group' => 'Clients']);
    }

    public function test_technician_only_sees_repairs(): void
    {
        $tech = $this->makeUserWithRole('technicien');

        $response = $this->actingAs($tech)
            ->getJson(route('global.search', ['q' => 'Samsung']));

        // Technicien ne doit pas voir de produits ni de clients dans les résultats
        $response->assertOk();
        $groups = collect($response->json('results'))->pluck('group')->unique()->values()->all();
        $this->assertNotContains('Produits', $groups);
        $this->assertNotContains('Clients', $groups);
    }
}
