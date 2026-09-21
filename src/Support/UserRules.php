<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraUser\Models\User;

final class UserRules
{
    public const int USERNAME_MIN_LENGTH = 3;

    public const int USERNAME_MAX_LENGTH = 12;

    public const int PASSWORD_LENGTH = 32;

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
     * Generate a password for a credential the caller never sees, such as a seeded
     * or command-issued one.
     *
     * The length and character set come from the application policy, because a
     * generated password is validated by the same rules a supplied one is.
     */
    public static function generatePassword(): string
    {
        $applied = Password::default()->appliedRules();
        $minimum = Arr::get($applied, 'min');
        $maximum = Arr::get($applied, 'max');

        $length = max(self::PASSWORD_LENGTH, is_int($minimum) ? $minimum : self::PASSWORD_LENGTH);

        if (is_int($maximum)) {
            $length = min($length, $maximum);
        }

        return Str::password($length, symbols: Arr::get($applied, 'symbols') === true);
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
}
