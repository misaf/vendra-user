<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraUser\Actions\CreateUserAction;
use Misaf\VendraUser\Models\User;
use Spatie\Permission\Models\Role;

final class CreateUserCommand extends Command
{
    protected $signature = 'user:create
        {--tenant=1 : Tenant ID for the new user}
        {--username= : Username for the new user}
        {--email= : Email address for the new user}
        {--password= : Password for the new user}
        {--role= : Role name to assign}
        {--guard=web : Guard name for the role}
        {--verified : Mark the user email as verified}';

    protected $description = 'Create a new user and assign a role';

    public function __construct(private readonly CreateUserAction $createUserAction)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenant = $this->resolveTenant();

        if ( ! $tenant) {
            return self::FAILURE;
        }

        $username = $this->requiredInput('username', 'Username');
        $email = $this->requiredInput('email', 'Email address');
        $password = $this->requiredInput('password', 'Password', secret: true);
        $roleName = $this->requiredInput(
            'role',
            'Role name',
            Config::string('vendra-permission.super_admin_role', 'super-admin'),
        );
        $guardName = $this->requiredInput('guard', 'Guard name', 'web');

        if (null === $username || null === $email || null === $password || null === $roleName || null === $guardName) {
            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error("A user with email [{$email}] already exists.");

            return self::FAILURE;
        }

        if (
            User::query()
                ->where('tenant_id', $tenant->id)
                ->where('username', $username)
                ->exists()
        ) {
            $this->error("A user with username [{$username}] already exists for tenant [{$tenant->id}].");

            return self::FAILURE;
        }

        $role = $this->resolveRole($roleName, $guardName, $tenant);

        if ( ! $role) {
            return self::FAILURE;
        }

        $user = $this->createUserAction->execute(
            $tenant,
            $username,
            $email,
            $password,
            (bool) $this->option('verified'),
        );

        $user->assignRole($role);

        $this->info("Created user {$user->username} ({$user->email}) and assigned role [{$role->name}].");

        return self::SUCCESS;
    }

    private function resolveTenant(): ?Tenant
    {
        $tenantId = (int) $this->option('tenant');
        $tenant = Tenant::query()->find($tenantId);

        if ( ! $tenant) {
            $this->error("Tenant with ID [{$tenantId}] not found.");

            return null;
        }

        return $tenant;
    }

    private function resolveRole(string $roleName, string $guardName, Tenant $tenant): ?Role
    {
        $roleQuery = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', $guardName);

        if (Schema::hasColumn('roles', 'tenant_id')) {
            $roleQuery->where('tenant_id', $tenant->id);
        }

        $role = $roleQuery->first();

        if ( ! $role) {
            $this->error("Role [{$roleName}] with guard [{$guardName}] not found for tenant [{$tenant->id}].");

            return null;
        }

        return $role;
    }

    private function requiredInput(string $option, string $label, ?string $default = null, bool $secret = false): ?string
    {
        $value = $this->option($option);

        if (is_string($value) && '' !== $value) {
            return $value;
        }

        if ( ! $this->input->isInteractive()) {
            $this->error("The --{$option} option is required.");

            return null;
        }

        $answer = $secret
            ? $this->secret($label)
            : $this->ask($label, $default);

        return is_string($answer) && '' !== $answer ? $answer : null;
    }
}
