<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Misaf\VendraUser\Models\User;
use Spatie\Permission\Models\Role;

final class AssignSuperAdminRoleCommand extends Command implements PromptsForMissingInput
{
    protected $signature = 'user:assign-super-admin {user_id=1 : The ID of the user to assign the super-admin role to}';

    protected $description = 'Assign the super-admin role to a specific user';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');

        $user = User::find($userId);

        if ( ! $user) {
            $this->error("User with ID {$userId} not found.");
            return self::FAILURE;
        }

        $superAdminRole = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();

        if ( ! $superAdminRole) {
            $this->error('Super-admin role not found with web guard. Please run the PermissionSeeder first.');
            return self::FAILURE;
        }

        if ($user->hasRole('super-admin')) {
            $this->info("User {$user->username} (ID: {$userId}) already has the super-admin role.");
            return self::SUCCESS;
        }

        $user->assignRole('super-admin');

        $this->info("Successfully assigned super-admin role to user {$user->username} (ID: {$userId}).");

        return self::SUCCESS;
    }
}
