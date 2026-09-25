<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Actions;

use Misaf\VendraUser\Models\User;

final class ResetUserAppAuthenticationAction
{
    /**
     * Remove the user's authenticator app and recovery codes, so a user who lost
     * both can sign in with their password and set it up again.
     */
    public function execute(User $user): User
    {
        $user->forceFill([
            'app_authentication_secret' => null,
            'app_authentication_recovery_codes' => null,
        ])->save();

        return $user;
    }
}
