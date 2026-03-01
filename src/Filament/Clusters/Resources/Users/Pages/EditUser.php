<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Misaf\VendraTransaction\Filament\Clusters\Resources\Transactions\Widgets\TransactionBonusOverviewWidget;
use Misaf\VendraTransaction\Filament\Clusters\Resources\Transactions\Widgets\TransactionCommissionOverviewWidget;
use Misaf\VendraTransaction\Filament\Clusters\Resources\Transactions\Widgets\TransactionDepositOverviewWidget;
use Misaf\VendraTransaction\Filament\Clusters\Resources\Transactions\Widgets\TransactionLimitOverviewWidget;
use Misaf\VendraTransaction\Filament\Clusters\Resources\Transactions\Widgets\TransactionWithdrawalOverviewWidget;
use Misaf\VendraUser\Filament\Clusters\Resources\UserLevels\Widgets\UserLevelOverviewWidget;
use Misaf\VendraUser\Filament\Clusters\Resources\UserRakeResource\Widgets\UserRakeOverviewWidget;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\UserResource;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getBreadcrumb(): string
    {
        return self::$breadcrumb ?? __('filament-panels::resources/pages/edit-record.breadcrumb') . ' ' . __('navigation.user');
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getHeaderWidgetsColumns(): array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
        ];
    }

    /**
     * @return array<class-string<Widget>|WidgetConfiguration>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            UserLevelOverviewWidget::class,
            UserRakeOverviewWidget::class,
            TransactionDepositOverviewWidget::class,
            TransactionWithdrawalOverviewWidget::class,
            TransactionBonusOverviewWidget::class,
            TransactionCommissionOverviewWidget::class,
            TransactionLimitOverviewWidget::class,
        ];
    }
}
