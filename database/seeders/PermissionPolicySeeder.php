<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Database\Seeders;

use Misaf\VendraSupport\Tenancy\Database\Seeders\PermissionPolicySeeder as BasePermissionPolicySeeder;
use Misaf\VendraUser\Enums\UserPolicyEnum;
use Misaf\VendraUser\UserPlugin;

final class PermissionPolicySeeder extends BasePermissionPolicySeeder
{
    protected const string MODULE_NAME = UserPlugin::ID;

    /**
     * Unlike the other modules, user permissions are never global: every user
     * belongs to a tenant, so seeding without a current tenant is an error
     * rather than a tenant-less install.
     */
    public function run(): void
    {
        $tenant = $this->currentTenant();

        $this->seedPermissionPolicies($tenant->getKey());
    }

    /**
     * @return list<string>
     */
    protected function policies(): array
    {
        return array_column(UserPolicyEnum::cases(), 'value');
    }
}
