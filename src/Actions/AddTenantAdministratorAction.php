<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\TenantAdministratorGuard;

final class AddTenantAdministratorAction
{
    public function __construct(
        private readonly CreateUserAction $createUserAction,
        private readonly TenantAdministratorGuard $guard,
    ) {}

    public function execute(
        Model $tenant,
        string $username,
        string $email,
        string $password,
        bool $verified = true,
    ): User {
        return DB::transaction(function () use ($tenant, $username, $email, $password, $verified): User {
            $user = $this->createUserAction->execute(
                tenant: $tenant,
                username: $username,
                email: $email,
                password: $password,
                isVerified: $verified,
                role: $this->guard->roleName(),
            );

            $user->tenants()->syncWithoutDetaching([$tenant->getKey()]);

            return $user;
        });
    }
}
