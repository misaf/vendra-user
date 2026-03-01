<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Misaf\VendraUser\Models\User;

final class UserEmailService
{
    public function getEmailDomain(User $user): ?string
    {
        if ( ! $user->email) {
            return null;
        }

        if ( ! Str::contains($user->email, '@')) {
            return null;
        }

        return Str::afterLast($user->email, '@');
    }

    public function isEmailVerified(User $user): bool
    {
        return null !== $user->email_verified_at;
    }

    public function getEmailVerificationStatus(User $user): string
    {
        if ($this->isEmailVerified($user)) {
            return 'Verified';
        }

        return 'Unverified';
    }

    public function getUsersByDomain(string $domain): Collection
    {
        return User::where('email', 'like', "%@{$domain}")->get();
    }

    /**
     * Get email domain statistics
     *
     * @return array
     */
    public function getDomainStatistics(): array
    {
        return User::selectRaw('
                CASE
                    WHEN email LIKE ? THEN SUBSTRING_INDEX(email, "@", -1)
                    ELSE NULL
                END as domain,
                COUNT(*) as count
            ', ['%@%'])
            ->groupBy('domain')
            ->having('domain', 'IS NOT', null)
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }
}
