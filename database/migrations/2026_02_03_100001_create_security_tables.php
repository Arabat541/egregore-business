<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables pour la sécurité du système
 */
return new class extends Migration
{
    public function up(): void
    {
        // Table des tentatives de connexion (pour anti brute-force)
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('failure_reason')->nullable(); // invalid_password, user_not_found, account_locked, account_inactive
            $table->timestamps();

            $table->index(['email', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });

        // Table des sessions actives
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('browser')->nullable();
            $table->string('platform')->nullable(); // Windows, Linux, Mac, Android, iOS
            $table->string('location')->nullable(); // Ville/Pays si disponible
            $table->timestamp('last_activity_at');
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'last_activity_at']);
        });

        // Table des alertes de sécurité
        Schema::create('security_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type'); // brute_force, suspicious_login, multiple_sessions, unusual_activity, account_locked
            $table->string('severity'); // low, medium, high, critical
            $table->string('ip_address', 45)->nullable();
            $table->text('description');
            $table->json('details')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->index(['is_resolved', 'severity']);
        });

        // Ajouter des colonnes de sécurité à la table users
        Schema::table('users', function (Blueprint $table) {
            $table->integer('failed_login_attempts')->default(0)->after('last_login_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('password_changed_at')->nullable()->after('locked_until');
            $table->boolean('force_password_change')->default(false)->after('password_changed_at');
            $table->string('last_login_ip', 45)->nullable()->after('force_password_change');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_alerts');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('login_attempts');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'failed_login_attempts',
                'locked_until',
                'password_changed_at',
                'force_password_change',
                'last_login_ip'
            ]);
        });
    }
};
