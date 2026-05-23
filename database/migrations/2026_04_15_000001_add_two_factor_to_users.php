<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes nécessaires au 2FA email-OTP sur la table users.
 *
 * - two_factor_enabled : active/désactive le 2FA pour cet utilisateur
 * - two_factor_code    : OTP à 6 chiffres (hashé en base)
 * - two_factor_expires_at : expiration de l'OTP (10 minutes)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_factor_enabled')->default(false)->after('is_active');
            $table->string('two_factor_code', 255)->nullable()->after('two_factor_enabled');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_enabled', 'two_factor_code', 'two_factor_expires_at']);
        });
    }
};
