<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Contracts;

use Misaf\VendraUser\Models\User;

/**
 * Decides panel access for one Filament panel owned by another package.
 *
 * Lives in `vendra-user` so the central identity can delegate without
 * naming console/reseller storage; each domain package registers its own
 * implementation from its service provider. Returning `false` denies,
 * returning `true` grants. The registry ORs multiple resolvers for the
 * same panel, so a single `true` is enough.
 */
interface PanelAccessResolver
{
    public function panelId(): string;

    public function canAccess(User $user): bool;
}
