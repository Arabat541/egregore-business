<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Implémentation native TOTP (RFC 6238) compatible Google Authenticator.
 * Aucune dépendance externe requise.
 */
class TotpService
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const TIME_STEP    = 30;
    private const DIGITS       = 6;
    private const WINDOW       = 1; // ±30 s de tolérance d'horloge

    // ── Génération du secret ────────────────────────────────────────────────

    /**
     * Génère un secret aléatoire encodé en base32 (160 bits = 32 caractères).
     */
    public function generateSecret(): string
    {
        $bytes = random_bytes(20);
        return $this->base32Encode($bytes);
    }

    // ── URL otpauth:// ──────────────────────────────────────────────────────

    /**
     * Retourne l'URL otpauth:// à encoder en QR code pour Google Authenticator.
     */
    public function getQrCodeUrl(string $issuer, string $account, string $secret): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($account);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            $label,
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::TIME_STEP
        );
    }

    // ── Vérification ────────────────────────────────────────────────────────

    /**
     * Vérifie un code TOTP avec une fenêtre temporelle.
     * Retourne false si le secret est invalide ou le code expiré.
     */
    public function verify(string $secret, string $code): bool
    {
        if (strlen($code) !== self::DIGITS || !ctype_digit($code)) {
            return false;
        }

        $secretBytes = $this->base32Decode($secret);
        if ($secretBytes === false) {
            return false;
        }

        $timestamp = (int) floor(time() / self::TIME_STEP);

        for ($offset = -self::WINDOW; $offset <= self::WINDOW; $offset++) {
            if ($this->hotp($secretBytes, $timestamp + $offset) === $code) {
                return true;
            }
        }

        return false;
    }

    // ── Algorithme HOTP (RFC 4226) ──────────────────────────────────────────

    private function hotp(string $secretBytes, int $counter): string
    {
        $counterBytes = pack('J', $counter); // unsigned 64-bit big-endian
        $hmac  = hash_hmac('sha1', $counterBytes, $secretBytes, true);
        $offset = ord($hmac[19]) & 0x0F;

        $code = (
            ((ord($hmac[$offset])     & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8)  |
            (ord($hmac[$offset + 3])  & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    // ── Base32 ─────────────────────────────────────────────────────────────

    private function base32Encode(string $bytes): string
    {
        $bits   = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split(str_pad($bits, (int) ceil(strlen($bits) / 5) * 5, '0'), 5) as $chunk) {
            $output .= self::BASE32_CHARS[bindec($chunk)];
        }

        return $output;
    }

    private function base32Decode(string $input): string|false
    {
        $input = strtoupper(trim($input));

        $bits = '';
        foreach (str_split($input) as $char) {
            $pos = strpos(self::BASE32_CHARS, $char);
            if ($pos === false) {
                return false;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) < 8) {
                break;
            }
            $output .= chr(bindec($chunk));
        }

        return $output ?: false;
    }
}
