<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Misaf\VendraSupport\Tenancy\Console\Commands\TenantSeedCommand;
use Misaf\VendraUser\Database\Seeders\DemoContentSeeder;
use Misaf\VendraUser\Database\Seeders\PermissionPolicySeeder;
use Misaf\VendraUser\UserPlugin;

#[Description('Seed user module data for a tenant')]
final class SeedCommand extends TenantSeedCommand
{
    protected const string MODULE_NAME = UserPlugin::ID;

    protected $signature = self::MODULE_NAME.':seed
        {tenant : Tenant ID or slug to seed user data for}
        {seeders?* : Seeder keys to run. Use "all" or one or more of: permission-policies, demo-contents}';

    /**
     * @return array<string, class-string>
     */
    protected function seeders(): array
    {
        return [
            'permission-policies' => PermissionPolicySeeder::class,
            'demo-contents' => DemoContentSeeder::class,
        ];
    }
}
