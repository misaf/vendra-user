<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraUser\Models\User;

final class UpdateUserPasswordAction
{
    public function __construct(private readonly TenantResolver $tenantResolver) {}

    public function execute(User $user, string $password): User
    {
        $tenant = $user->tenant()->firstOrFail();

        return $this->tenantResolver->execute($tenant, fn(): User => DB::transaction(function () use ($user, $password): User {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $lockedUser->forceFill([
                'password'       => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            return $lockedUser;
        }));
    }
}
