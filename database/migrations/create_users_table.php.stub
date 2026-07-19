<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraSupport\Support\TenantSchema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        $this->createUsersTable();
        $this->createPasswordResetTokensTable();
        $this->createSessionsTable();
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::enableForeignKeyConstraints();
    }

    private function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            TenantSchema::addTenantColumn($table);
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

            TenantSchema::addTenantIndex($table);
            $table->unique(TenantSchema::tenantIndex(['username']));
            $table->unique(TenantSchema::tenantIndex(['active_email_guard']), 'users_active_email_unique');
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
