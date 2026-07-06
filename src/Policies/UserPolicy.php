<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Misaf\VendraUser\Enums\UserPolicyEnum;

final class UserPolicy
{
    use HandlesAuthorization;

    public function create(Authorizable $user): bool
    {
        return $user->can(UserPolicyEnum::CREATE->value);
    }

    public function delete(Authorizable $user): bool
    {
        return $user->can(UserPolicyEnum::DELETE->value);
    }

    public function deleteAny(Authorizable $user): bool
    {
        return $user->can(UserPolicyEnum::DELETE_ANY->value);
    }

    public function forceDelete(Authorizable $user): bool
    {
        return $user->can(UserPolicyEnum::FORCE_DELETE->value);
    }

    public function forceDeleteAny(Authorizable $user): bool
    {
        return $user->can(UserPolicyEnum::FORCE_DELETE_ANY->value);
    }

    public function replicate(Authorizable $user): bool
    {
        return $user->can(UserPolicyEnum::REPLICATE->value);
    }

    public function restore(Authorizable $user): bool
    {
        return $user->can(UserPolicyEnum::RESTORE->value);
    }

    public function restoreAny(Authorizable $user): bool
    {
        return $user->can(UserPolicyEnum::RESTORE_ANY->value);
    }

    public function update(Authorizable $user): bool
    {
        return $user->can(UserPolicyEnum::UPDATE->value);
    }

    public function view(Authorizable $user): bool
    {
        return $user->can(UserPolicyEnum::VIEW->value);
    }

    public function viewAny(Authorizable $user): bool
    {
        return $user->can(UserPolicyEnum::VIEW_ANY->value);
    }
}
