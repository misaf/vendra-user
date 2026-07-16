<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Widgets;

use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Misaf\VendraUser\Models\User;

final class UserOverviewWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $startDate = now()->startOfWeek();
        $endDate = now()->endOfWeek();
        $users = User::query();
        $verifiedUsers = User::query()->whereNotNull('email_verified_at');
        $unverifiedUsers = User::query()->whereNull('email_verified_at');
        $userTrend = Trend::query(clone $users)->between($startDate, $endDate)->perDay()->count();
        $verifiedUserTrend = Trend::query(clone $verifiedUsers)->between($startDate, $endDate)->perDay()->count();
        $unverifiedUserTrend = Trend::query(clone $unverifiedUsers)->between($startDate, $endDate)->perDay()->count();

        return [
            Stat::make('total_user_stats', Number::format($users->count()))
                ->chart($this->chartValues($userTrend))
                ->color('primary')
                ->description(__('vendra-user::widgets.total_users_description'))
                ->descriptionIcon(Heroicon::OutlinedUsers, IconPosition::Before)
                ->icon(Heroicon::OutlinedUsers)
                ->label(__('vendra-user::widgets.total_users')),
            Stat::make('verified_user_stats', Number::format($verifiedUsers->count()))
                ->chart($this->chartValues($verifiedUserTrend))
                ->color('success')
                ->description(__('vendra-user::widgets.verified_users_description'))
                ->descriptionIcon(Heroicon::OutlinedCheckBadge, IconPosition::Before)
                ->icon(Heroicon::OutlinedUsers)
                ->label(__('vendra-user::widgets.verified_users')),
            Stat::make('unverified_user_stats', Number::format($unverifiedUsers->count()))
                ->chart($this->chartValues($unverifiedUserTrend))
                ->color('warning')
                ->description(__('vendra-user::widgets.unverified_users_description'))
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle, IconPosition::Before)
                ->icon(Heroicon::OutlinedUsers)
                ->label(__('vendra-user::widgets.unverified_users')),
        ];
    }

    /**
     * @param Collection<(int|string), mixed> $values
     *
     * @return list<float>
     */
    private function chartValues(Collection $values): array
    {
        $chart = [];

        foreach ($values as $value) {
            if ($value instanceof TrendValue && is_numeric($value->aggregate)) {
                $chart[] = (float) $value->aggregate;
            }
        }

        return $chart;
    }
}
