<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Misaf\VendraSupport\Tenancy\Console\Commands\TenantSeedCommand;
use Misaf\VendraUser\Database\Seeders\DemoContentSeeder;
use Misaf\VendraUser\Database\Seeders\PermissionPolicySeeder;
use Misaf\VendraUser\UserPlugin;

/**
 * The tenant argument is required here, unlike every other module: user
 * permissions are never global, so this command has nothing to seed without
 * one.
 */
#[Description('Seed user module data for a tenant')]
#[Signature(self::MODULE_NAME.':seed
        {tenant : Tenant ID or slug to seed user data for}
        {seeders?* : Seeder keys to run. Use "all" or one or more of: permission-policies, demo-contents}')]
final class SeedCommand extends TenantSeedCommand
{
    protected const string MODULE_NAME = UserPlugin::ID;

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
