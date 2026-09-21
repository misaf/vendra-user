<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraUser\Models\User;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

function createUserCommandRole(Model|int|string $tenant, string $name = 'admin'): void
{
    resolve(TenantResolver::class)->execute(
        $tenant,
        fn () => resolve(PermissionRegistrar::class)->getRoleClass()::create([
            'name' => $name,
            'guard_name' => 'web',
        ]),
    );
}

it('rejects credentials that fail the shared user rules', function (string $username, string $password): void {
    $tenant = createTestTenant();
    createUserCommandRole($tenant);

    $this->artisan('vendra-user:create', [
        '--tenant' => $tenant->getKey(),
        '--username' => $username,
        '--email' => 'florist@example.test',
        '--password' => $password,
        '--role' => 'admin',
    ])->assertFailed();

    assertDatabaseMissing('users', ['email' => 'florist@example.test']);
})->with([
    'invalid username' => ['bad.name', 'secret-password'],
    'weak password' => ['florist', 'short'],
]);

it('rejects an email that fails the shared user rules', function (string $email): void {
    $tenant = createTestTenant();
    createUserCommandRole($tenant);

    $this->artisan('vendra-user:create', [
        '--tenant' => $tenant->getKey(),
        '--username' => 'florist',
        '--email' => $email,
        '--password' => 'secret-password',
        '--role' => 'admin',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
})->with([
    'no domain' => 'not-an-email',
    'whitespace only' => '   ',
    'spoofable' => 'florist@exampłe.test',
]);

it('normalizes the email before creating the user', function (): void {
    $tenant = createTestTenant();
    createUserCommandRole($tenant);

    $this->artisan('vendra-user:create', [
        '--tenant' => $tenant->getKey(),
        '--username' => 'florist',
        '--email' => '  Florist@Example.test ',
        '--password' => 'secret-password',
        '--role' => 'admin',
    ])
        ->expectsOutput('Created user florist (florist@example.test) and assigned role [admin].')
        ->assertSuccessful();

    assertDatabaseHas('users', ['email' => 'florist@example.test']);
});

it('creates the user inside the selected tenant and assigns the role', function (): void {
    $tenant = createTestTenant();
    createUserCommandRole($tenant);

    $this->artisan('vendra-user:create', [
        '--tenant' => $tenant->getKey(),
        '--username' => 'florist',
        '--email' => 'florist@example.test',
        '--password' => 'secret-password',
        '--role' => 'admin',
    ])
        ->expectsOutput('Created user florist (florist@example.test) and assigned role [admin].')
        ->assertSuccessful();

    assertDatabaseHas('users', [
        'username' => 'florist',
        'email' => 'florist@example.test',
        TenantSchema::column() => $tenant->getKey(),
    ]);
});

it('creates the user in a tenant given by slug', function (): void {
    $tenant = createTestTenant();
    createUserCommandRole($tenant);

    $this->artisan('vendra-user:create', [
        '--tenant' => $tenant->slug,
        '--username' => 'florist',
        '--email' => 'florist@example.test',
        '--password' => 'secret-password',
        '--role' => 'admin',
    ])->assertSuccessful();

    assertDatabaseHas('users', [
        'username' => 'florist',
        TenantSchema::column() => $tenant->getKey(),
    ]);
});

it('fails when the selected tenant does not exist', function (): void {
    $this->artisan('vendra-user:create', [
        '--tenant' => 999,
        '--username' => 'florist',
        '--email' => 'florist@example.test',
        '--password' => 'secret-password',
        '--role' => 'admin',
    ])
        ->expectsOutput('Tenant [999] not found.')
        ->assertFailed();

    assertDatabaseMissing('users', ['email' => 'florist@example.test']);
});

it('prompts for an option that was not passed', function (): void {
    $tenant = createTestTenant();
    createUserCommandRole($tenant);

    $this->artisan('vendra-user:create', [
        '--tenant' => $tenant->getKey(),
        '--username' => 'florist',
        '--email' => 'florist@example.test',
        '--role' => 'admin',
    ])
        ->expectsQuestion('Password', 'secret-password')
        ->assertSuccessful();

    assertDatabaseHas('users', ['email' => 'florist@example.test']);
});

it('aborts when a prompted option is left empty', function (): void {
    $tenant = createTestTenant();
    createUserCommandRole($tenant);

    $this->artisan('vendra-user:create', [
        '--tenant' => $tenant->getKey(),
        '--username' => 'florist',
        '--email' => 'florist@example.test',
        '--role' => 'admin',
    ])
        ->expectsQuestion('Password', '')
        ->assertFailed();

    assertDatabaseMissing('users', ['email' => 'florist@example.test']);
});

/*
 | Emails are unique across the whole installation, not per tenant, so a second
 | tenant reusing one has to be refused before the action runs.
 */
it('refuses an email another tenant already uses', function (): void {
    $otherTenant = createTestTenant();
    $tenant = createTestTenant();
    createUserCommandRole($tenant);

    resolve(TenantResolver::class)->execute(
        $otherTenant,
        fn (): User => User::factory()->create(['email' => 'florist@example.test']),
    );

    $this->artisan('vendra-user:create', [
        '--tenant' => $tenant->getKey(),
        '--username' => 'florist',
        '--email' => 'florist@example.test',
        '--password' => 'secret-password',
        '--role' => 'admin',
    ])
        ->expectsOutput('A user with email [florist@example.test] already exists.')
        ->assertFailed();
});

it('refuses a username the selected tenant already uses', function (): void {
    $tenant = createTestTenant();
    createUserCommandRole($tenant);

    resolve(TenantResolver::class)->execute(
        $tenant,
        fn (): User => User::factory()->create(['username' => 'florist']),
    );

    $this->artisan('vendra-user:create', [
        '--tenant' => $tenant->getKey(),
        '--username' => 'florist',
        '--email' => 'florist@example.test',
        '--password' => 'secret-password',
        '--role' => 'admin',
    ])
        ->expectsOutput("A user with username [florist] already exists for tenant [{$tenant->getKey()}].")
        ->assertFailed();
});

it('fails when the role does not exist for the selected tenant', function (): void {
    $otherTenant = createTestTenant();
    $tenant = createTestTenant();
    createUserCommandRole($otherTenant);

    $this->artisan('vendra-user:create', [
        '--tenant' => $tenant->getKey(),
        '--username' => 'florist',
        '--email' => 'florist@example.test',
        '--password' => 'secret-password',
        '--role' => 'admin',
    ])
        ->expectsOutput("Role [admin] with guard [web] not found for tenant [{$tenant->getKey()}].")
        ->assertFailed();

    assertDatabaseMissing('users', ['email' => 'florist@example.test']);
});
