<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remplace le 2FA par email-OTP par Google Authenticator (TOTP, RFC 6238).
 *
 * - Ajoute   : two_factor_secret (secret base32 permanent)
 * - Supprime : two_factor_code   (OTP éphémère haché)
 * - Supprime : two_factor_expires_at
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('two_factor_secret', 64)->nullable()->after('two_factor_enabled');
            $table->dropColumn(['two_factor_code', 'two_factor_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('two_factor_secret');
            $table->string('two_factor_code', 255)->nullable()->after('two_factor_enabled');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code');
        });
    }
};
