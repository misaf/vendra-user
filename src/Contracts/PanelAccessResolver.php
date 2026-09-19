<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Contracts;

use Misaf\VendraUser\Models\User;

/**
 * Any resolver returning true grants access to its panel.
 */
interface PanelAccessResolver
{
    public function panelId(): string;

    public function canAccess(User $user): bool;
}
