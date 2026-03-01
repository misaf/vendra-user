<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

final class UserRakeOverviewWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    public ?Model $record = null;

    protected int|string|array $columnSpan = [
        'sm' => 1,
    ];

    protected function getColumns(): int
    {
        return 1;
    }

    protected static ?int $sort = 1;

    public static function isDiscovered(): bool
    {
        return true;
    }

    public static function canView(): bool
    {
        return true;
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        // 1) Resolve cache & Redis via facades
        $cache = Cache::store('user_rake');
        $redis = Redis::connection(config('cache.stores.user_rake.connection'));
        $prefix = config('cache.prefix') . ':';

        // 2) Determine key bases
        $userId = $this->record?->id;
        if ($userId) {
            $dailyBase = "user-rake-stats:user:{$userId}:daily:";
            $totalKey = "user-rake-stats:user:{$userId}";
        } else {
            $dailyBase = 'user-rake-stats:daily:';
            $totalKey = 'user-rake-stats';
        }

        // 3) Grab all daily‐rake keys from Redis
        $pattern = $prefix . $dailyBase . '*';
        $dailyKeys = $redis->keys($pattern);
        $dateToRake = [];

        foreach ($dailyKeys as $fullKey) {
            $day = Str::afterLast($fullKey, ':');
            if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
                continue;
            }
            $dateToRake[$day] = (float) $cache->get($dailyBase . $day, 0);
        }

        // 4) Sort dates
        if ( ! empty($dateToRake)) {
            ksort($dateToRake);
        }

        // 5) Apply start/end filters if both exist
        if ( ! empty($this->pageFilters['start_date']) && ! empty($this->pageFilters['end_date'])) {
            $start = Carbon::parse($this->pageFilters['start_date']);
            $end = Carbon::parse($this->pageFilters['end_date']);

            $dateToRake = array_filter(
                $dateToRake,
                fn(float $rake, string $day) => Carbon::parse($day)->between($start, $end),
                ARRAY_FILTER_USE_BOTH,
            );
        }

        // 6) Determine first & last month in data (or default to this month)
        if ( ! empty($dateToRake)) {
            $days = array_keys($dateToRake);
            $firstMonth = Carbon::parse($days[0])->startOfMonth();
            $lastMonth = Carbon::parse(end($days))->startOfMonth();
        } else {
            $firstMonth = now()->startOfMonth();
            $lastMonth = now()->startOfMonth();
        }

        // 7) Build “YYYY-MM” list between first & last month
        $months = [];
        for ($dt = $firstMonth->copy(); $dt->lte($lastMonth); $dt->addMonth()) {
            $months[] = $dt->format('Y-m');
        }

        // 8) Bucket daily into monthly sums
        $monthlyRake = array_fill_keys($months, 0.0);
        foreach ($dateToRake as $day => $rake) {
            $monthKey = Str::substr($day, 0, 7);
            if (isset($monthlyRake[$monthKey])) {
                $monthlyRake[$monthKey] += $rake;
            }
        }

        // 9) Prepare chart data (ensure at least 2 points)
        $chartData = array_values($monthlyRake);
        if (count($chartData) < 2) {
            $fill = $chartData[0] ?? 0.0;
            $chartData = array_pad($chartData, 2, $fill);
        }

        // 10) Fetch total rake
        $totalRake = (float) $cache->get($totalKey, 0);

        // 12) Return the Stat widget
        return [
            Stat::make('user_rake_stats', Number::format($totalRake))
                ->label(__('user-rake::widgets.user_rake_stats'))
                ->description(__('user-rake::widgets.user_rake_stats_description'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($chartData)
                ->color('primary'),
        ];
    }
}
