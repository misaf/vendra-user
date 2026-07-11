<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraUser\Models\User;
use Spatie\Permission\Contracts\Role;

final class CreateUserAction
{
    public function execute(
        Model $tenant,
        string $username,
        string $email,
        string $password,
        bool $isVerified = true,
        Role|string|null $role = null,
    ): User {
        /** @var User $user */
        $user = app(TenantResolver::class)->execute($tenant, function () use ($username, $email, $password, $role, $isVerified): User {
            /** @var User $user */
            $user = User::query()->create([
                'username'          => $username,
                'email'             => $email,
                'email_verified_at' => $isVerified ? Carbon::now() : null,
                'password'          => Hash::make($password),
            ]);

            if (null !== $role) {
                $user->assignRole($role);
            }

            return $user;
        });

        return $user;
    }
}
