<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Misaf\VendraUser\Enums\UserPolicyEnum;
use Misaf\VendraUser\Models\User;

final class UserPolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return $user->can(UserPolicyEnum::CREATE);
    }

    public function delete(User $user): bool
    {
        return $user->can(UserPolicyEnum::DELETE);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can(UserPolicyEnum::DELETE_ANY);
    }

    public function forceDelete(User $user): bool
    {
        return $user->can(UserPolicyEnum::FORCE_DELETE);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can(UserPolicyEnum::FORCE_DELETE_ANY);
    }

    public function replicate(User $user): bool
    {
        return $user->can(UserPolicyEnum::REPLICATE);
    }

    public function restore(User $user): bool
    {
        return $user->can(UserPolicyEnum::RESTORE);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can(UserPolicyEnum::RESTORE_ANY);
    }

    public function update(User $user): bool
    {
        return $user->can(UserPolicyEnum::UPDATE);
    }

    public function view(User $user): bool
    {
        return $user->can(UserPolicyEnum::VIEW);
    }

    public function viewAny(User $user): bool
    {
        return $user->can(UserPolicyEnum::VIEW_ANY);
    }
}
