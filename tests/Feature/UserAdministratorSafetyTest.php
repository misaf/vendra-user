<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Actions\DeleteUserBulkAction;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Actions\DeleteUserTableAction;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\EditUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\ListUsers;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\TenantAdministratorGuard;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    setUpFilamentAdminTestContext();
});

it('refuses to delete the last administrator from the table', function (): void {
    $administrator = Filament::auth()->user();

    livewire(ListUsers::class)
        ->callAction(TestAction::make(DeleteUserTableAction::class)->table($administrator))
        ->assertNotified(__('vendra-user::forms.last_administrator_required'));

    expect($administrator->fresh()->trashed())->toBeFalse();
});

it('refuses to delete the last administrator from the edit page', function (): void {
    $administrator = Filament::auth()->user();

    livewire(EditUser::class, ['record' => $administrator->getRouteKey()])
        ->callAction(DeleteUserTableAction::class)
        ->assertNotified(__('vendra-user::forms.last_administrator_required'));

    expect($administrator->fresh()->trashed())->toBeFalse();
});

it('keeps one administrator when every administrator is selected for bulk deletion', function (): void {
    $administrator = Filament::auth()->user();
    $second = User::factory()->create();
    $second->assignRole(resolve(TenantAdministratorGuard::class)->role());
    $customer = User::factory()->create();

    livewire(ListUsers::class)
        ->selectTableRecords([$administrator, $second, $customer])
        ->callAction(TestAction::make(DeleteUserBulkAction::class)->table()->bulk())
        ->assertNotified(trans_choice('filament-actions::delete.multiple.notifications.deleted_partial.title', 2, [
            'count' => '2',
            'total' => '3',
        ]));

    expect(User::query()->administratorOf(currentTestTenant())->count())->toBe(1)
        ->and($customer->fresh()->trashed())->toBeTrue();
});

it('refuses to remove the last administrator role and rolls back other edits', function (): void {
    $administrator = Filament::auth()->user();
    $email = $administrator->email;
    $password = $administrator->password;

    livewire(EditUser::class, ['record' => $administrator->getRouteKey()])
        ->fillForm(['roles' => [], 'email' => 'changed@example.com', 'password' => 'NewPassword123'])
        ->call('save')
        ->assertNotified(__('vendra-user::forms.last_administrator_required'));

    $administrator->refresh();
    expect($administrator->hasRole(resolve(TenantAdministratorGuard::class)->role()))->toBeTrue()
        ->and($administrator->email)->toBe($email)
        ->and($administrator->password)->toBe($password);
});

it('allows deletion when another enabled administrator has no membership pivot', function (): void {
    $administrator = Filament::auth()->user();
    $second = User::factory()->create();
    $second->assignRole(resolve(TenantAdministratorGuard::class)->role());
    expect($second->tenants()->exists())->toBeFalse();

    livewire(ListUsers::class)
        ->callAction(TestAction::make(DeleteUserTableAction::class)->table($second));

    expect($second->fresh()->trashed())->toBeTrue()
        ->and($administrator->fresh()->trashed())->toBeFalse();
});

it('does not count a disabled administrator as a replacement', function (): void {
    $administrator = Filament::auth()->user();
    $disabled = User::factory()->create();
    $disabled->assignRole(resolve(TenantAdministratorGuard::class)->role());
    $disabled->delete();

    livewire(ListUsers::class)
        ->callAction(TestAction::make(DeleteUserTableAction::class)->table($administrator))
        ->assertNotified(__('vendra-user::forms.last_administrator_required'));

    expect($administrator->fresh()->trashed())->toBeFalse();
});

it('allows demotion while another administrator remains', function (): void {
    $second = User::factory()->create(['username' => 'second_admin']);
    $second->assignRole(resolve(TenantAdministratorGuard::class)->role());

    livewire(EditUser::class, ['record' => $second->getRouteKey()])
        ->fillForm(['roles' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($second->fresh()->roles()->count())->toBe(0);
});

it('allows deleting a customer while the last administrator remains', function (): void {
    $customer = User::factory()->create();

    livewire(ListUsers::class)
        ->callAction(TestAction::make(DeleteUserTableAction::class)->table($customer));

    expect($customer->fresh()->trashed())->toBeTrue()
        ->and(User::query()->administratorOf(currentTestTenant())->count())->toBe(1);
});
