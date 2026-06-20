<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Database\Seeders;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Misaf\VendraSupport\Database\Seeders\TenantDemoContentSeeder;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraUser\Database\Factories\UserFactory;
use Misaf\VendraUser\Models\User;
use Spatie\Permission\Models\Role;

final class DemoContentSeeder extends TenantDemoContentSeeder
{
    protected function seedFactoryRecords(Tenant $tenant): void
    {
        $adminUser = UserFactory::new()->forTenant($tenant)->createOne();

        $this->assignRole($adminUser, Config::string('vendra-permission.super_admin_role', 'super-admin'));

        UserFactory::new()->forTenant($tenant)->count(2)->create();
        UserFactory::new()->forTenant($tenant)->unverified()->createOne();
    }

    protected function seedFixtureRecord(Tenant $tenant, array $record): void
    {
        $data = $this->validatedFixtureRecord($record);

        $this->handleSeedFixtureRecord($tenant, $data);
    }

    /**
     * @param array{username: string, email: string, email_verified: bool, role?: string} $data
     */
    private function handleSeedFixtureRecord(Tenant $tenant, array $data): void
    {
        $user = new User([
            'username'          => $data['username'],
            'email'             => $data['email'],
            'email_verified_at' => $data['email_verified'] ? now() : null,
            'password'          => Hash::make(Str::password(32)),
        ]);

        $user->tenant_id = $tenant->id;
        $user->save();

        if (isset($data['role'])) {
            $this->assignRole($user, $data['role']);
        }
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array{username: string, email: string, email_verified: bool, role?: string}
     */
    private function validatedFixtureRecord(array $record): array
    {
        /** @var array{username: string, email: string, email_verified: bool, role?: string} $validated */
        $validated = Validator::make(
            data: $record,
            rules: [
                'username'       => ['required', 'string'],
                'email'          => ['required', 'email'],
                'email_verified' => ['required', 'boolean'],
                'role'           => ['sometimes', 'string'],
            ],
        )->validate();

        return $validated;
    }

    private function assignRole(User $user, string $roleName): void
    {
        $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

        if ($role) {
            $user->assignRole($role);
        }
    }
}
