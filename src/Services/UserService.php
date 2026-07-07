<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Services;

final class UserService
{
    private const PASSWORD_CHARACTERS = '123456789abcdefghijklmnopqrstuvwxyz';

    private const PASSWORD_LENGTH = 8;

    public function generatePassword(?int $length = null): string
    {
        $length ??= self::PASSWORD_LENGTH;
        $maxIndex = mb_strlen(self::PASSWORD_CHARACTERS) - 1;

        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= self::PASSWORD_CHARACTERS[random_int(0, $maxIndex)];
        }

        return $password;
    }
}
