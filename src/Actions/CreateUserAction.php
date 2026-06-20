<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraUser\Models\User;

final class CreateUserAction
{
    public function execute(
        Tenant $tenant,
        string $username,
        string $email,
        string $password,
        bool $isVerified,
    ): User {
        $user = User::query()->create([
            'tenant_id'         => $tenant->id,
            'username'          => $username,
            'email'             => $email,
            'email_verified_at' => $isVerified ? Carbon::now() : null,
            'password'          => Hash::make($password),
        ]);

        return $user;
    }
}
