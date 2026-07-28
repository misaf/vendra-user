<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Schemas;

use Filament\Infolists\Components\SpatieTagsEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Misaf\VendraSupport\Capabilities\TagIntegration;
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
            self::dateEntry('email_verified_at'),
            self::dateEntry('created_at'),
            self::dateEntry('updated_at'),
        ];

        if (TagIntegration::isAvailable()) {
            $components[] = SpatieTagsEntry::make('tags')
                ->columnSpanFull()
                ->label(__('vendra-support::attributes.tags'))
                ->type(User::TAG_TYPE);
        }

        return $schema
            ->components($components)
            ->columns(2);
    }

    private static function dateEntry(string $name): TextEntry
    {
        return TextEntry::make($name)
            ->label(__("vendra-user::attributes.{$name}"))
            ->when(
                app()->isLocale('fa'),
                fn(TextEntry $entry): TextEntry => $entry->jalaliDateTime('Y-m-d H:i', latinNumbers: true),
                fn(TextEntry $entry): TextEntry => $entry->dateTime('Y-m-d H:i'),
            );
    }
}
