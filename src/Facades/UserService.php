<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string generatePassword(?int $length = null)
 *
 * @see \Misaf\VendraUser\Services\UserService
 */
final class UserService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Misaf\VendraUser\Services\UserService::class;
    }
}
