<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        $this->createUsersTable();
        $this->createSocialiteUsersTable();
        $this->createPasswordResetTokensTable();
        $this->createSessionsTable();
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('users');
        Schema::dropIfExists('socialite_users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::enableForeignKeyConstraints();
    }

    private function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
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

            $table->index(['tenant_id']);
            $table->index(['tenant_id', 'username']);
            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'password_fingerprint']);
        });
    }

    private function createSocialiteUsersTable(): void
    {
        Schema::create('socialite_users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('provider');
            $table->string('provider_id');
            $table->timestampsTz();

            $table->unique([
                'provider',
                'provider_id',
            ]);
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
