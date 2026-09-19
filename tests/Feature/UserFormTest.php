<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Misaf\VendraUser\Actions\CreateUserAction;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\CreateUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\EditUser;
use Misaf\VendraUser\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->tenant = setUpFilamentAdminTestContext();
});

it('rejects a duplicate username within the current tenant', function (): void {
    User::factory()->create(['username' => 'demo-user']);

    livewire(CreateUser::class)
        ->fillForm([
            'username' => 'demo-user',
            'email' => 'demo-user@gmail.com',
            'password' => 'secret-password',
        ])
        ->call('create')
        ->assertHasFormErrors(['username']);
});

it('allows the same username in another tenant', function (): void {
    $otherTenant = createTestTenant();

    // Filament associates created users with the panel tenant, so point it at the other tenant.
    Filament::setTenant($otherTenant);

    $otherUser = resolve(CreateUserAction::class)->execute(
        tenant: $otherTenant,
        username: 'demo-user',
        email: 'other-tenant@example.com',
        password: 'secret-password',
    );

    Filament::setTenant($this->tenant);

    expect($otherUser->tenant_id)->toBe($otherTenant->getKey());

    livewire(CreateUser::class)
        ->fillForm([
            'username' => 'demo-user',
            'email' => 'demo-user@gmail.com',
            'password' => 'secret-password',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->withoutGlobalScopes()->where('username', 'demo-user')->count())->toBe(2);
});

it('creates the user in the panel tenant with a hashed password', function (): void {
    livewire(CreateUser::class)
        ->fillForm([
            'username' => 'demo-user',
            'email' => 'demo-user@gmail.com',
            'password' => 'secret-password',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('username', 'demo-user')->sole();

    expect($user->tenant_id)->toBe($this->tenant->getKey())
        ->and(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($user->email_verified_at)->toBeNull();
});

it('rotates the remember token when an administrator changes the password', function (): void {
    $user = User::factory()->create(['username' => 'demo-user', 'email' => 'demo-user@gmail.com', 'remember_token' => 'old-token']);

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->fillForm(['password' => 'new-secret-password'])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect(Hash::check('new-secret-password', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe('old-token');
});

it('keeps the password when the field is left blank', function (): void {
    $user = User::factory()->create(['username' => 'demo-user', 'email' => 'demo-user@gmail.com', 'remember_token' => 'old-token']);
    $passwordHash = $user->password;

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->password)->toBe($passwordHash)
        ->and($user->remember_token)->toBe('old-token');
});
