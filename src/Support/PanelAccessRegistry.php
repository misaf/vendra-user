<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Support;

use Filament\Panel;
use Misaf\VendraUser\Contracts\PanelAccessResolver;
use Misaf\VendraUser\Models\User;

/**
 * Container-bound registry of per-panel access resolvers.
 *
 * Bound as an application singleton (see `UserServiceProvider`), so each
 * test's fresh application starts empty and package providers populate it
 * during boot — no static global state. `vendra-user` owns only this
 * infrastructure; the console and reseller packages own their resolvers.
 */
final class PanelAccessRegistry
{
    /**
     * @var array<string, list<PanelAccessResolver>>
     */
    private array $resolvers = [];

    public function register(PanelAccessResolver $resolver): void
    {
        $this->resolvers[$resolver->panelId()][] = $resolver;
    }

    public function has(string $panelId): bool
    {
        return ($this->resolvers[$panelId] ?? []) !== [];
    }

    /**
     * Resolve access for the given panel, or null when no package claimed it.
     */
    public function canAccess(User $user, Panel $panel): ?bool
    {
        $resolvers = $this->resolvers[$panel->getId()] ?? [];

        if ($resolvers === []) {
            return null;
        }

        foreach ($resolvers as $resolver) {
            if ($resolver->canAccess($user)) {
                return true;
            }
        }

        return false;
    }
}
