<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Database\Seeders;

use Misaf\VendraSupport\Database\Seeders\PermissionPolicySeeder as BasePermissionPolicySeeder;
use Misaf\VendraUser\Enums\UserPolicyEnum;
use Misaf\VendraUser\UserPlugin;

final class PermissionPolicySeeder extends BasePermissionPolicySeeder
{
    protected const string MODULE_NAME = UserPlugin::ID;

    /**
     * @return list<string>
     */
    protected function policies(): array
    {
        return array_column(UserPolicyEnum::cases(), 'value');
    }
}
