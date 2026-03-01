<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Services;

final class UserService
{
    private const PASSWORD_CHARACTERS = '123456789abcdefghijklmnopqrstuvwxyz';

    private const PASSWORD_LENGTH = 8;

    public function generatePassword(?int $lenght): string
    {
        return mb_substr(str_shuffle(str_repeat(self::PASSWORD_CHARACTERS, $lenght ?? self::PASSWORD_LENGTH)), 0, $lenght ?? self::PASSWORD_LENGTH);
    }
}
