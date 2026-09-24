<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Tables\Filters\QueryBuilder\Constraints;

use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;

/**
 * A date constraint, not a boolean one: comparing a timestamp to true matches no rows on MySQL.
 */
final class EmailVerifiedAtConstraint extends DateConstraint
{
    public static function getDefaultName(): string
    {
        return 'email_verified_at';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('vendra-user::attributes.email_verified_at'))
            ->nullable();
    }
}
