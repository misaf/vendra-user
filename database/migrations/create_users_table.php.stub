<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraSupport\Tenancy\TenantSchema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        $this->createUsersTable();
        $this->createPasswordResetTokensTable();
        $this->createConsolePasswordResetTokensTable();
        $this->createResellerPasswordResetTokensTable();
        $this->createSessionsTable();
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('console_password_reset_tokens');
        Schema::dropIfExists('reseller_password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::enableForeignKeyConstraints();
    }

    private function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            TenantSchema::addTenantColumn($table, nullable: true);
            $table->string('username');
            $table->string('email');
            $table->timestampTz('email_verified_at')
                ->nullable();
            $table->string('password');
            $table->string('password_fingerprint', 64)
                ->nullable();
            $table->rememberToken();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->string('active_email_guard')
                ->nullable()
                ->virtualAs('CASE WHEN deleted_at IS NULL THEN email ELSE NULL END');
            /*
            | Platform-level identities (console users, reseller users)
            | carry a null tenant id, where the tenant-scoped uniques below
            | stop discriminating. These guards keep emails and usernames
            | globally unique for those rows only. Without a tenant provider
            | the tenant-scoped uniques below are already global, so the
            | guards are only needed when tenancy is enabled.
            */
            if (TenantSchema::enabled()) {
                $table->string('global_email_guard')
                    ->nullable()
                    ->virtualAs('CASE WHEN deleted_at IS NULL AND '.TenantSchema::column().' IS NULL THEN email ELSE NULL END');
                $table->string('global_username_guard')
                    ->nullable()
                    ->virtualAs('CASE WHEN deleted_at IS NULL AND '.TenantSchema::column().' IS NULL THEN username ELSE NULL END');
            }
            TenantSchema::addTenantIndex($table);
            $table->unique(TenantSchema::tenantIndex(['username']));
            $table->unique(TenantSchema::tenantIndex(['active_email_guard']), 'users_active_email_unique');
            if (TenantSchema::enabled()) {
                $table->unique('global_email_guard', 'users_global_email_unique');
                $table->unique('global_username_guard', 'users_global_username_unique');
            }
            $table->index(TenantSchema::tenantIndex(['email']));
            $table->index(TenantSchema::tenantIndex(['password_fingerprint']));
        });
    }

    private function createPasswordResetTokensTable(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')
                ->primary();
            $table->string('token');
            $table->timestampTz('created_at')
                ->nullable();
        });
    }

    /**
     * Console users reset their password through the `console` broker, which
     * stores tokens here. Reset tokens are keyed by email alone, so every
     * scope needs its own store: a tenant user may share an email with a
     * platform identity, and one platform identity may hold both a console
     * grant and a reseller membership, so any shared table would let one
     * scope overwrite or consume another's token. The shape mirrors
     * `password_reset_tokens` so the framework's token repository works
     * unchanged.
     */
    private function createConsolePasswordResetTokensTable(): void
    {
        Schema::create('console_password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')
                ->primary();
            $table->string('token');
            $table->timestampTz('created_at')
                ->nullable();
        });
    }

    /**
     * Reseller users reset their password through the `reseller` broker,
     * which stores tokens here. See
     * {@see self::createConsolePasswordResetTokensTable()} for why each scope
     * keeps its own token store.
     */
    private function createResellerPasswordResetTokensTable(): void
    {
        Schema::create('reseller_password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')
                ->primary();
            $table->string('token');
            $table->timestampTz('created_at')
                ->nullable();
        });
    }

    private function createSessionsTable(): void
    {
        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')
                ->primary();
            $table->foreignId('user_id')
                ->nullable()
                ->index();
            $table->string('ip_address', 45)
                ->nullable();
            $table->text('user_agent')
                ->nullable();
            $table->longText('payload');
            $table->integer('last_activity')
                ->index();
        });
    }
};
