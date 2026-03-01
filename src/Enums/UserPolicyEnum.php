<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Enums;

enum UserPolicyEnum: string
{
    case CREATE = 'create-user';
    case DELETE = 'delete-user';
    case DELETE_ANY = 'delete-any-user';
    case FORCE_DELETE = 'force-delete-user';
    case FORCE_DELETE_ANY = 'force-delete-any-user';
    case REPLICATE = 'replicate-user';
    case RESTORE = 'restore-user';
    case RESTORE_ANY = 'restore-any-user';
    case UPDATE = 'update-user';
    case VIEW = 'view-user';
    case VIEW_ANY = 'view-any-user';
}
