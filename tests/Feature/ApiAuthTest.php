<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests de l'API REST — authentification Sanctum
 */
class ApiAuthTest extends TestCase
{

    public function test_login_returns_token(): void
    {
        Role::firstOrCreate(['name' => 'caissiere', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'email'     => 'cashier@test.com',
            'password'  => bcrypt('password123'),
            'is_active' => true,
        ]);
        $user->assignRole('caissiere');

        $response = $this->postJson(route('api.login'), [
            'email'       => 'cashier@test.com',
            'password'    => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['token', 'token_type', 'user'])
                 ->assertJsonPath('token_type', 'Bearer');
    }

    public function test_invalid_credentials_return_422(): void
    {
        $response = $this->postJson(route('api.login'), [
            'email'    => 'nobody@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email'     => 'inactive@test.com',
            'password'  => bcrypt('password'),
            'is_active' => false,
        ]);

        $response = $this->postJson(route('api.login'), [
            'email'    => 'inactive@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403);
    }

    public function test_me_endpoint_requires_auth(): void
    {
        $this->getJson(route('api.me'))->assertStatus(401);
    }

    public function test_me_returns_user_data(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');

        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer $token")
             ->getJson(route('api.me'))
             ->assertOk()
             ->assertJsonPath('id', $user->id)
             ->assertJsonStructure(['id', 'name', 'email', 'roles']);
    }

    public function test_logout_revokes_token(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer $token")
             ->postJson(route('api.logout'))
             ->assertOk();

        // Reset the Sanctum guard so the next request re-resolves from the DB.
        $this->app['auth']->forgetGuards();

        // Token should now be invalid
        $this->withHeader('Authorization', "Bearer $token")
             ->getJson(route('api.me'))
             ->assertStatus(401);
    }
}
