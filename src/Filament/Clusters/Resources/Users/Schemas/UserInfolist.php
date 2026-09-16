<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Misaf\VendraSupport\Capabilities\TagIntegration;
use Misaf\VendraSupport\Filament\Infolists\Components\CreatedAtEntry;
use Misaf\VendraSupport\Filament\Infolists\Components\DateTimeEntry;
use Misaf\VendraSupport\Filament\Infolists\Components\UpdatedAtEntry;
use Misaf\VendraTagger\Filament\Infolists\Components\ModelTagsEntry;
use Misaf\VendraUser\Models\User;

final class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $components = [
            TextEntry::make('username')->label(__('vendra-user::attributes.username')),
            TextEntry::make('email')
                ->copyable()
                ->label(__('vendra-user::attributes.email')),
            TextEntry::make('roles.name')
                ->badge()
                ->columnSpanFull()
                ->label(__('vendra-permission::navigation.roles')),
            TextEntry::make('permissions.name')
                ->badge()
                ->columnSpanFull()
                ->label(__('vendra-permission::navigation.permissions')),
            DateTimeEntry::make('email_verified_at')
                ->label(__('vendra-user::attributes.email_verified_at')),
            CreatedAtEntry::make(),
            UpdatedAtEntry::make(),
        ];

        if (TagIntegration::isAvailable()) {
            $components[] = ModelTagsEntry::make()
                ->type(User::TAG_TYPE);
        }

        return $schema
            ->components($components)
            ->columns(2);
    }
}
