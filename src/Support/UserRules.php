<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Misaf\LaravelEmailVerification\Rules\EmailValidation;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraUser\Models\User;

final class UserRules
{
    /**
     * @return list<mixed>
     */
    public static function email(): array
    {
        return ['bail', 'email:rfc,strict,spoof,filter,filter_unicode', new EmailValidation];
    }

    /**
     * Require a username or email unique among one tenant's users, or among platform users when the tenant is null.
     *
     * Soft-deleted users release their values, as the users table indexes do.
     */
    public static function unique(string $column, ?int $tenantId = null, ?int $ignoreUserId = null): Unique
    {
        $rule = Rule::unique(User::class, $column)->ignore($ignoreUserId)->withoutTrashed();

        if (! TenantSchema::enabled()) {
            return $rule;
        }

        return $tenantId === null
            ? $rule->whereNull(TenantSchema::column())
            : $rule->where(TenantSchema::column(), $tenantId);
    }
}
