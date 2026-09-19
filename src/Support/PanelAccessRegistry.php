<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Support;

use Filament\Panel;
use Misaf\VendraUser\Contracts\PanelAccessResolver;
use Misaf\VendraUser\Models\User;

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
     * Determine panel access, or null when no package claims the panel.
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
