<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Database\Seeders;

use Illuminate\Database\Seeder;
use Misaf\VendraCurrency\Models\Currency;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Models\UserProfile;
use Spatie\Permission\Models\Role;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->first();

        if ( ! $tenant) {
            $this->command->error('Tenants not found. Please run TenantSeeder first.');
            return;
        }

        $defaultCurrency = Currency::first();

        if ( ! $defaultCurrency) {
            $this->command->error('No currency found. Please ensure currencies are seeded first.');
            return;
        }

        $this->createSampleUsers($tenant, $defaultCurrency, 'misaf-shops');
    }

    private function createSampleUsers(Tenant $tenant, Currency $currency, string $tenantSlug): void
    {
        $adminUser = User::factory()->forTenant($tenant)->create([
            'username'          => "admin_{$tenantSlug}",
            'email'             => "admin@{$tenantSlug}.test",
            'email_verified_at' => now(),
        ]);

        // Assign super-admin role to admin user
        $superAdminRole = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdminRole) {
            $adminUser->assignRole($superAdminRole);
            $this->command->info("Assigned super-admin role to admin user (ID: {$adminUser->id})");
        } else {
            $this->command->warn("Super-admin role not found. Please run PermissionSeeder first.");
        }

        // Create regular user
        $regularUser = User::factory()->forTenant($tenant)->create([
            'username'          => "user_{$tenantSlug}",
            'email'             => "user@{$tenantSlug}.test",
            'email_verified_at' => now(),
        ]);

        $regularProfile = UserProfile::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => $regularUser->id,
            'first_name'  => 'Regular',
            'last_name'   => 'User',
            'description' => "Regular user for {$tenantSlug} tenant",
            'birthdate'   => null,
            'status'      => true,
        ]);


        // Create unverified user
        $unverifiedUser = User::factory()->forTenant($tenant)->unverified()->create([
            'username' => "unverified_{$tenantSlug}",
            'email'    => "unverified@{$tenantSlug}.test",
        ]);

        $this->command->info("Created sample users for {$tenantSlug} tenant:");
        $this->command->info("- Admin user: admin@{$tenantSlug}.test");
        $this->command->info("- Regular user: user@{$tenantSlug}.test");
        $this->command->info("- Unverified user: unverified@{$tenantSlug}.test");
    }
}
