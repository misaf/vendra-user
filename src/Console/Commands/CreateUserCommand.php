<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use LogicException;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraUser\Actions\CreateUserAction;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\UserRules;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\PermissionRegistrar;

#[Description('Create a new user and assign a role')]
#[Signature('vendra-user:create
        {--tenant=1 : Tenant ID or slug for the new user}
        {--username= : Username for the new user}
        {--email= : Email address for the new user}
        {--password= : Password for the new user}
        {--role= : Role name to assign}
        {--guard=web : Guard name for the role}')]
final class CreateUserCommand extends Command
{
    public function __construct(private readonly CreateUserAction $createUserAction)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenant = $this->resolveTenant();

        if (! $tenant) {
            return self::FAILURE;
        }

        $tenantKey = $tenant->getKey();

        if (! is_int($tenantKey) && ! is_string($tenantKey)) {
            $this->error('The tenant has no usable key.');

            return self::FAILURE;
        }

        $username = $this->requiredInput('username', 'Username');
        $email = $this->requiredInput('email', 'Email address');
        $password = $this->requiredInput('password', 'Password', secret: true);
        $role = $this->requiredInput(
            'role',
            'Role name',
            Config::string('vendra-permission.admin_role'),
        );
        $guardName = $this->requiredInput('guard', 'Guard name', 'web');

        if ($username === null || $email === null || $password === null || $role === null || $guardName === null) {
            return self::FAILURE;
        }

        $email = Str::lower(mb_trim($email));
        $validator = Validator::make(
            ['username' => $username, 'email' => $email, 'password' => $password],
            [
                'username' => ['required', ...UserRules::username()],
                'email' => ['required', ...UserRules::email()],
                'password' => ['required', ...UserRules::password()],
            ],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error("A user with email [{$email}] already exists.");

            return self::FAILURE;
        }

        if (
            User::query()
                ->where(TenantSchema::column(), $tenantKey)
                ->where('username', $username)
                ->exists()
        ) {
            $this->error("A user with username [{$username}] already exists for tenant [{$tenantKey}].");

            return self::FAILURE;
        }

        try {
            $resolvedRole = resolve(TenantResolver::class)->execute(
                $tenant,
                fn (): Role => $this->roleModelClass()::findByName($role, $guardName),
            );

            $user = $this->createUserAction->execute(
                tenant: $tenant,
                username: $username,
                email: $email,
                password: $password,
                role: $resolvedRole,
            );
        } catch (RoleDoesNotExist) {
            $this->error("Role [{$role}] with guard [{$guardName}] not found for tenant [{$tenantKey}].");

            return self::FAILURE;
        }

        $this->info("Created user {$user->username} ({$user->email}) and assigned role [{$role}].");

        return self::SUCCESS;
    }

    private function resolveTenant(): ?Model
    {
        $tenantIdentifier = (string) $this->option('tenant');
        $tenant = resolve(TenantResolver::class)->findByKeyOrSlug($tenantIdentifier);

        if (! $tenant) {
            $this->error("Tenant [{$tenantIdentifier}] not found.");

            return null;
        }

        return $tenant;
    }

    private function requiredInput(string $option, string $label, ?string $default = null, bool $secret = false): ?string
    {
        $value = $this->option($option);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (! $this->input->isInteractive()) {
            $this->error("The --{$option} option is required.");

            return null;
        }

        $answer = $secret
            ? $this->secret($label)
            : $this->ask($label, $default);

        return is_string($answer) && $answer !== '' ? $answer : null;
    }

    /**
     * @return class-string<Role>
     */
    private function roleModelClass(): string
    {
        $roleModelClass = resolve(PermissionRegistrar::class)->getRoleClass();

        throw_unless(is_a($roleModelClass, Role::class, true), LogicException::class, "The configured role model [{$roleModelClass}] must implement the role contract.");

        return $roleModelClass;
    }
}
