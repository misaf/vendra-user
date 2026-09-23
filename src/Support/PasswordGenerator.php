<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

final class PasswordGenerator
{
    public const int LENGTH = 32;

    /**
     * Generate a password for a credential the caller never sees, such as a seeded
     * or command-issued one.
     *
     * The length and character set come from the application policy, because a
     * generated password is validated by the same rules a supplied one is.
     */
    public static function generate(): string
    {
        $applied = Password::default()->appliedRules();
        $minimum = Arr::get($applied, 'min');
        $maximum = Arr::get($applied, 'max');

        $length = max(self::LENGTH, is_int($minimum) ? $minimum : self::LENGTH);

        if (is_int($maximum)) {
            $length = min($length, $maximum);
        }

        return Str::password($length, symbols: Arr::get($applied, 'symbols') === true);
    }
}
