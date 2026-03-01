<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string generatePassword(?int $lenght)
 * @method static int|bool updateUserRake(int $userId, float $rake)
 * @method static int|bool updateDailyUserRake(int $userId, float $rake, ?string $timestamp = null)
 * @method static int|bool updateRake(float $rake)
 * @method static int|bool updateDailyRake(float $rake, ?string $timestamp = null)
 *
 * @see Misaf\User\Services\UserService
 */
final class UserService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'user-service';
    }
}
