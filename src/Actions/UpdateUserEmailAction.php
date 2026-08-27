<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraUser\Models\User;

final class UpdateUserEmailAction
{
    public function __construct(private readonly TenantResolver $tenantResolver) {}

    public function execute(User $user, string $email, bool $verified = true): User
    {
        $tenant = $user->tenant()->firstOrFail();

        return $this->tenantResolver->execute($tenant, fn(): User => DB::transaction(function () use ($user, $email, $verified, $tenant): User {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            Validator::make(['email' => $email], [
                'email' => [
                    'required',
                    'email',
                    Rule::unique(User::class, 'email')
                        ->where(TenantSchema::column(), $tenant->getKey())
                        ->ignore($lockedUser->getKey()),
                ],
            ])->validate();

            $lockedUser->forceFill([
                'email'             => $email,
                'email_verified_at' => $verified ? now() : null,
            ])->save();

            return $lockedUser;
        }));
    }
}
