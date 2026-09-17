<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraUser\Models\User;

final readonly class UpdateUserPasswordAction
{
    public function __construct(private TenantResolver $tenantResolver) {}

    public function execute(User $user, string $password): User
    {
        $update = fn (): User => DB::transaction(function () use ($user, $password): User {
            $lockedUser = $user->refreshForUpdate();

            throw_if($lockedUser->trashed(), (new ModelNotFoundException)->setModel(User::class));

            $lockedUser->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            return $lockedUser;
        });

        $tenant = $user->tenant()->first();

        /*
        | Platform-level identities (console and reseller users) hold no
        | tenant, so there is no tenant context to enter for them.
        */
        if ($tenant === null) {
            return $update();
        }

        return $this->tenantResolver->execute($tenant, $update);
    }
}
