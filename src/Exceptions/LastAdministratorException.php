<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Exceptions;

use LogicException;

final class LastAdministratorException extends LogicException
{
    public static function forTenant(int|string $tenantId): self
    {
        return new self("Tenant [{$tenantId}] must retain at least one enabled administrator.");
    }
}
