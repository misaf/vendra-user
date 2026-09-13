<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Support\Facades\DB;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraUser\Models\User;

final readonly class UpdateUserEmailAction
{
    public function __construct(private TenantResolver $tenantResolver) {}

    public function execute(User $user, string $email, bool $verified = true): User
    {
        $tenant = $user->tenant()->firstOrFail();

        return $this->tenantResolver->execute($tenant, fn (): User => DB::transaction(function () use ($user, $email, $verified): User {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $lockedUser->forceFill([
                'email' => $email,
                'email_verified_at' => $verified ? now() : null,
            ])->save();

            return $lockedUser;
        }));
    }
}
