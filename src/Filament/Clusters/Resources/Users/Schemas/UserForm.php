<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component as Livewire;

use Misaf\VendraSupport\Support\TagIntegration;
use Misaf\VendraSupport\Support\TenantAwareness;
use Misaf\VendraUser\Facades\UserService;
use Misaf\VendraUser\Rules\EmailValidation;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $components = [
            TextInput::make('username')
                ->afterStateUpdated(fn(Livewire $livewire) => $livewire->validateOnly('data.username'))
                ->autofocus()
                ->dehydrated(fn(string $operation): bool => 'create' === $operation)
                ->disabledOn('edit')
                ->extraAttributes(['dir' => 'ltr'])
                ->helperText(__('vendra-user::forms.username_helper_text'))
                ->label(__('vendra-user::attributes.username'))
                ->live(onBlur: true)
                ->maxLength(12)
                ->minLength(3)
                ->required()
                ->rules(['alpha_dash'])
                ->unique(
                    modifyRuleUsing: fn(Unique $rule): Unique => TenantAwareness::constrainUniqueRule($rule)
                        ->withoutTrashed(),
                ),

            TextInput::make('email')
                ->afterStateUpdated(fn(Livewire $livewire) => $livewire->validateOnly('data.email'))
                ->columnSpan(['lg' => 2])
                ->email()
                ->extraAttributes(['dir' => 'ltr'])
                ->label(__('vendra-user::attributes.email'))
                ->live(onBlur: true)
                ->maxLength(255)
                ->required()
                ->rules(['bail', 'email:rfc,strict,spoof,filter,filter_unicode', new EmailValidation(app()->isProduction())])
                ->unique(
                    modifyRuleUsing: fn(Unique $rule): Unique => TenantAwareness::constrainUniqueRule($rule)
                        ->withoutTrashed(),
                ),

            DateTimePicker::make('email_verified_at')
                ->closeOnDateSelection()
                ->displayFormat('Y-m-d H:i')
                ->firstDayOfWeek(6)
                ->label(__('vendra-user::attributes.email_verified_at'))
                ->maxDate(now())
                ->native(false)
                ->seconds(false),

            TextInput::make('password')
                ->dehydrated(fn($state): bool => filled($state))
                ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                ->extraAttributes(['dir' => 'ltr'])
                ->hintAction(
                    Action::make('generatePassword')
                        ->action(fn(Set $set) => $set('password', UserService::generatePassword(10)))
                        ->disabled(fn(string $operation): bool => 'view' === $operation)
                        ->icon(Heroicon::OutlinedShieldCheck)
                        ->iconPosition(fn(): IconPosition => app()->isLocale('fa') ? IconPosition::After : IconPosition::Before)
                        ->label(__('vendra-user::forms.random_password')),
                )
                ->label(__('vendra-user::attributes.password'))
                ->live(debounce: 500)
                ->password()
                ->required(fn(string $operation): bool => 'create' === $operation)
                ->revealable(filament()->arePasswordsRevealable())
                ->rule(Password::default()),

            Select::make('roles')
                ->label(__('vendra-permission::navigation.role'))
                ->multiple()
                ->native(false)
                ->preload()
                ->relationship('roles', 'name')
                ->searchable(),

            Select::make('permissions')
                ->label(__('vendra-permission::navigation.permission'))
                ->multiple()
                ->native(false)
                ->preload()
                ->relationship('permissions', 'name')
                ->searchable(),
        ];

        if (TagIntegration::isAvailable()) {
            $components[] = SpatieTagsInput::make('tags')
                ->columnSpanFull()
                ->label(__('vendra-support::attributes.tags'))
                ->type(\Misaf\VendraUser\Models\User::TAG_TYPE);
        }

        return $schema
            ->components($components);
    }

}
