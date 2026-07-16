<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Enums;

enum UserPolicyEnum: string
{
    case Create = 'create-user';
    case Delete = 'delete-user';
    case DeleteAny = 'delete-any-user';
    case ForceDelete = 'force-delete-user';
    case ForceDeleteAny = 'force-delete-any-user';
    case Replicate = 'replicate-user';
    case Restore = 'restore-user';
    case RestoreAny = 'restore-any-user';
    case Update = 'update-user';
    case View = 'view-user';
    case ViewAny = 'view-any-user';
}
