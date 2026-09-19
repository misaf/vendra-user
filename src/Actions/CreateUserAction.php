<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraUser\Models\User;
use Spatie\Permission\Contracts\Role;

final class CreateUserAction
{
    /**
     * A null tenant creates a platform user, such as a console or reseller user.
     */
    public function execute(
        ?Model $tenant,
        string $username,
        string $email,
        string $password,
        bool $isVerified = true,
        Role|string|null $role = null,
    ): User {
        if ($tenant === null) {
            return DB::transaction(function () use ($username, $email, $password, $role, $isVerified): User {
                $user = $this->createUser($username, $email, $password, $role, $isVerified, platformLevel: true);

                if ($user->tenant_id !== null) {
                    /*
                    | The tenant hook stamps the current tenant when there is
                    | one (tests, callers inside tenant middleware). A
                    | platform-level identity must never belong to a tenant,
                    | so the stamp is reverted on the same transaction.
                    */
                    $user->forceFill(['tenant_id' => null])->save();
                }

                return $user;
            });
        }

        /** @var User $user */
        $user = resolve(TenantResolver::class)->execute(
            $tenant,
            fn (): User => $this->createUser($username, $email, $password, $role, $isVerified),
        );

        return $user;
    }

    private function createUser(
        string $username,
        string $email,
        string $password,
        Role|string|null $role,
        bool $isVerified,
        bool $platformLevel = false,
    ): User {
        /** @var User $user */
        $user = User::query()->create([
            ...($platformLevel ? ['tenant_id' => null] : []),
            'username' => $username,
            'email' => $email,
            'email_verified_at' => $isVerified ? Date::now() : null,
            'password' => Hash::make($password),
        ]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }
}
