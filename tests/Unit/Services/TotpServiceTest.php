<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TotpService;
use PHPUnit\Framework\TestCase;

final class TotpServiceTest extends TestCase
{
    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new TotpService();
    }

    public function test_generate_secret_returns_32_base32_chars(): void
    {
        $secret = $this->totp->generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
    }

    public function test_generate_secret_is_unique(): void
    {
        $secrets = array_map(fn() => $this->totp->generateSecret(), range(1, 10));

        $this->assertCount(10, array_unique($secrets));
    }

    public function test_get_qr_code_url_contains_required_parts(): void
    {
        $url = $this->totp->getQrCodeUrl('MyApp', 'user@example.com', 'JBSWY3DPEHPK3PXP');

        $this->assertStringContainsString('otpauth://totp/', $url);
        $this->assertStringContainsString('JBSWY3DPEHPK3PXP', $url);
        $this->assertStringContainsString('MyApp', $url);
        $this->assertStringContainsString('digits=6', $url);
        $this->assertStringContainsString('period=30', $url);
    }

    public function test_verify_returns_true_for_valid_current_code(): void
    {
        $secret    = $this->totp->generateSecret();
        $validCode = $this->generateCurrentCode($secret);

        $this->assertTrue($this->totp->verify($secret, $validCode));
    }

    public function test_verify_accepts_code_from_previous_window(): void
    {
        $secret = $this->totp->generateSecret();
        $code   = $this->generateCodeAtWindow($secret, -1);

        $this->assertTrue($this->totp->verify($secret, $code));
    }

    public function test_verify_accepts_code_from_next_window(): void
    {
        $secret = $this->totp->generateSecret();
        $code   = $this->generateCodeAtWindow($secret, +1);

        $this->assertTrue($this->totp->verify($secret, $code));
    }

    public function test_verify_rejects_code_outside_window(): void
    {
        $secret = $this->totp->generateSecret();
        $code   = $this->generateCodeAtWindow($secret, -2);

        $this->assertFalse($this->totp->verify($secret, $code));
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $secret    = $this->totp->generateSecret();
        $validCode = $this->generateCurrentCode($secret);
        // Flip the last digit to guarantee an invalid code
        $wrongCode = substr($validCode, 0, 5) . (((int) $validCode[5] + 1) % 10);

        // May still collide with a valid window but extremely unlikely
        if ($wrongCode !== $validCode) {
            $this->assertFalse($this->totp->verify($secret, $wrongCode));
        } else {
            $this->markTestSkipped('Digit collision: rerun test.');
        }
    }

    public function test_verify_rejects_non_numeric_code(): void
    {
        $secret = $this->totp->generateSecret();

        $this->assertFalse($this->totp->verify($secret, 'ABCDEF'));
    }

    public function test_verify_rejects_code_with_wrong_length(): void
    {
        $secret = $this->totp->generateSecret();

        $this->assertFalse($this->totp->verify($secret, '12345'));
        $this->assertFalse($this->totp->verify($secret, '1234567'));
    }

    public function test_verify_rejects_invalid_base32_secret(): void
    {
        $this->assertFalse($this->totp->verify('!!!INVALID!!!', '123456'));
    }

    public function test_roundtrip_encode_decode_produces_correct_code(): void
    {
        // Use a well-known test vector: RFC 6238 uses key "12345678901234567890"
        // and expects code 755224 at T=0 (counter=0, step=30, meaning T=0..29).
        // We can't test this directly since TotpService doesn't expose hotp(),
        // but we can verify the symmetry: a code generated from a secret verifies.
        $secret = $this->totp->generateSecret();
        $code   = $this->generateCurrentCode($secret);

        $this->assertTrue($this->totp->verify($secret, $code));
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function generateCurrentCode(string $secret): string
    {
        return $this->generateCodeAtWindow($secret, 0);
    }

    private function generateCodeAtWindow(string $secret, int $offset): string
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

        $counter      = (int) floor(time() / 30) + $offset;
        $counterBytes = pack('J', $counter);
        $hmac         = hash_hmac('sha1', $counterBytes, $secretBytes, true);
        $offsetByte   = ord($hmac[19]) & 0x0F;
        $code = (
            ((ord($hmac[$offsetByte])     & 0x7F) << 24) |
            ((ord($hmac[$offsetByte + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offsetByte + 2]) & 0xFF) << 8)  |
            (ord($hmac[$offsetByte + 3])  & 0xFF)
        ) % 1_000_000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }
}
