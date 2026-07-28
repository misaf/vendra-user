<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Database\Seeders;

use Misaf\VendraSupport\Tenancy\Database\Seeders\PermissionPolicySeeder as BasePermissionPolicySeeder;
use Misaf\VendraSupport\Tenancy\RequiresCurrentTenant;
use Misaf\VendraUser\Enums\UserPolicyEnum;
use Misaf\VendraUser\UserPlugin;

final class PermissionPolicySeeder extends BasePermissionPolicySeeder
{
    use RequiresCurrentTenant;

    protected const string MODULE_NAME = UserPlugin::ID;

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
