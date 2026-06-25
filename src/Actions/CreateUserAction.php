<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Misaf\VendraPermission\Models\Role;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraUser\Models\User;

final class CreateUserAction
{
    public function execute(
        Tenant $tenant,
        string $username,
        string $email,
        string $password,
        bool $isVerified = true,
        Role|string|null $role = null,
    ): User {
        /** @var User $user */
        $user = $tenant->execute(function () use ($username, $email, $password, $role, $isVerified): User {
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
