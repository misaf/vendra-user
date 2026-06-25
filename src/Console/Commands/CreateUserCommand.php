<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Misaf\VendraPermission\Models\Role;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraUser\Actions\CreateUserAction;
use Misaf\VendraUser\Models\User;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

final class CreateUserCommand extends Command
{
    protected $signature = 'user:create
        {--tenant=1 : Tenant ID for the new user}
        {--username= : Username for the new user}
        {--email= : Email address for the new user}
        {--password= : Password for the new user}
        {--role= : Role name to assign}
        {--guard=web : Guard name for the role}';

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
        $role = $this->requiredInput(
            'role',
            'Role name',
            Config::string('vendra-permission.super_admin_role', 'super-admin'),
        );
        $guardName = $this->requiredInput('guard', 'Guard name', 'web');

        if (null === $username || null === $email || null === $password || null === $role || null === $guardName) {
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

        try {
            /** @var Role $resolvedRole */
            $resolvedRole = $tenant->execute(static fn() => Role::findByName($role, $guardName));

            $user = $this->createUserAction->execute(
                tenant: $tenant,
                username: $username,
                email: $email,
                password: $password,
                role: $resolvedRole,
            );
        } catch (RoleDoesNotExist) {
            $this->error("Role [{$role}] with guard [{$guardName}] not found for tenant [{$tenant->id}].");

            return self::FAILURE;
        }

        $this->info("Created user {$user->username} ({$user->email}) and assigned role [{$role}].");

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
