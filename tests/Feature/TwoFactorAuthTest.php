<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Services\TotpService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests du flow 2FA Google Authenticator (TOTP).
 */
class TwoFactorAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function makeShop(): Shop
    {
        return Shop::firstOrCreate(['code' => 'TST1'], ['name' => 'Test Shop', 'is_active' => true]);
    }

    /**
     * Generate a valid TOTP code for the given base32 secret at the current timestamp.
     * Replicates TotpService internals so tests don't depend on non-public methods.
     */
    private function totpCode(string $secret, int $offsetWindow = 0): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits  = '';
        foreach (str_split(strtoupper(trim($secret))) as $char) {
            $pos   = strpos($chars, $char);
            $bits .= str_pad(decbin((int) $pos), 5, '0', STR_PAD_LEFT);
        }
        $secretBytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) < 8) {
                break;
            }
            $secretBytes .= chr((int) bindec($chunk));
        }

        $counter      = (int) floor(time() / 30) + $offsetWindow;
        $counterBytes = pack('J', $counter);
        $hmac         = hash_hmac('sha1', $counterBytes, $secretBytes, true);
        $offset       = ord($hmac[19]) & 0x0F;
        $code = (
            ((ord($hmac[$offset])     & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8)  |
            (ord($hmac[$offset + 3])  & 0xFF)
        ) % 1_000_000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    public function test_login_with_2fa_disabled_goes_directly_to_dashboard(): void
    {
        Role::firstOrCreate(['name' => 'caissiere', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'password'           => bcrypt('password'),
            'is_active'          => true,
            'two_factor_enabled' => false,
            'shop_id'            => $this->makeShop()->id,
        ]);
        $user->assignRole('caissiere');

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
             ->assertRedirect(route('cashier.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_2fa_enabled_redirects_to_2fa_form(): void
    {
        Role::firstOrCreate(['name' => 'caissiere', 'guard_name' => 'web']);

        $secret = app(TotpService::class)->generateSecret();
        $user   = User::factory()->create([
            'password'           => bcrypt('password'),
            'is_active'          => true,
            'two_factor_enabled' => true,
            'two_factor_secret'  => $secret,
            'shop_id'            => $this->makeShop()->id,
        ]);
        $user->assignRole('caissiere');

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
             ->assertRedirect(route('2fa.show'));

        $this->assertGuest();
    }

    public function test_correct_2fa_code_authenticates_user(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $user   = User::factory()->create([
            'is_active'          => true,
            'two_factor_enabled' => true,
            'two_factor_secret'  => $secret,
        ]);

        $this->withSession(['2fa_pending_user_id' => $user->id])
             ->post(route('2fa.verify'), ['code' => $this->totpCode($secret)])
             ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_2fa_code_is_rejected(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $user   = User::factory()->create([
            'is_active'          => true,
            'two_factor_enabled' => true,
            'two_factor_secret'  => $secret,
        ]);

        $this->withSession(['2fa_pending_user_id' => $user->id])
             ->post(route('2fa.verify'), ['code' => '000000'])
             ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_2fa_setup_enables_totp_after_valid_code(): void
    {
        $user   = User::factory()->create([
            'is_active'          => true,
            'two_factor_enabled' => false,
            'shop_id'            => $this->makeShop()->id,
        ]);
        $secret = app(TotpService::class)->generateSecret();

        $this->actingAs($user)
             ->withSession(['2fa_setup_secret' => $secret])
             ->post(route('profile.2fa.confirm'), ['code' => $this->totpCode($secret)])
             ->assertRedirect(route('profile.edit'));

        $this->assertTrue($user->fresh()->two_factor_enabled);
        $this->assertSame($secret, $user->fresh()->two_factor_secret);
    }

    public function test_2fa_disable_requires_password(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $user   = User::factory()->create([
            'is_active'          => true,
            'two_factor_enabled' => true,
            'two_factor_secret'  => $secret,
            'shop_id'            => $this->makeShop()->id,
            'password'           => bcrypt('correct-password'),
        ]);

        // Wrong password rejected
        $this->actingAs($user)
             ->post(route('profile.2fa.disable'), ['password' => 'wrong-password'])
             ->assertSessionHasErrors('password');
        $this->assertTrue($user->fresh()->two_factor_enabled);

        // Correct password disables 2FA
        $this->actingAs($user)
             ->post(route('profile.2fa.disable'), ['password' => 'correct-password'])
             ->assertRedirect();
        $this->assertFalse($user->fresh()->two_factor_enabled);
        $this->assertNull($user->fresh()->two_factor_secret);
    }
}
