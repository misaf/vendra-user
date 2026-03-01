<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\UserResource;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getBreadcrumb(): string
    {
        return self::$breadcrumb ?? __('filament-panels::resources/pages/create-record.breadcrumb') . ' ' . __('navigation.user');
    }
}
