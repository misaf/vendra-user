<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraUser\Models\User;

final class UserRules
{
    public const int USERNAME_MIN_LENGTH = 3;

    public const int USERNAME_MAX_LENGTH = 12;

    /**
     * @return list<string>
     */
    public static function username(): array
    {
        return ['string', 'min:'.self::USERNAME_MIN_LENGTH, 'max:'.self::USERNAME_MAX_LENGTH, 'alpha_dash'];
    }

    /**
     * @return list<string|Password>
     */
    public static function password(): array
    {
        return ['string', Password::default()];
    }

    /**
     * @return list<string>
     */
    public static function email(): array
    {
        return ['bail', 'email:rfc,strict,spoof,filter,filter_unicode'];
    }

    /**
     * Require a username or email unique among one tenant's users, or among tenantless users when the tenant is null.
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

    /**
     * Require a username or email held by one tenant's users, or by tenantless users when the tenant is null.
     *
     * Soft-deleted users are left out, as they are by unique().
     */
    public static function exists(string $column, ?int $tenantId = null): Exists
    {
        $rule = Rule::exists(User::class, $column)->withoutTrashed();

        if (! TenantSchema::enabled()) {
            return $rule;
        }

        return $tenantId === null
            ? $rule->whereNull(TenantSchema::column())
            : $rule->where(TenantSchema::column(), $tenantId);
    }
}
