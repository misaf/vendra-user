<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component as Livewire;

use Misaf\LaravelEmailVerification\Rules\EmailValidation;
use Misaf\VendraSupport\Capabilities\TagIntegration;
use Misaf\VendraSupport\Filament\Actions\GeneratePasswordAction;
use Misaf\VendraSupport\Tenancy\TenantAwareness;

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
                ->rules(['bail', 'email:rfc,strict,spoof,filter,filter_unicode', new EmailValidation()])
                ->unique(
                    modifyRuleUsing: fn(Unique $rule): Unique => TenantAwareness::constrainUniqueRule($rule)
                        ->withoutTrashed(),
                ),

            DateTimePicker::make('email_verified_at')
                ->afterStateUpdated(fn(Livewire $livewire) => $livewire->validateOnly('data.email_verified_at'))
                ->closeOnDateSelection()
                ->displayFormat('Y-m-d H:i')
                ->firstDayOfWeek(6)
                ->helperText(__('vendra-user::attributes.email_verified_at_helper_text'))
                ->label(__('vendra-user::attributes.email_verified_at'))
                ->live()
                ->maxDate(now())
                ->native(false)
                ->seconds(false),

            TextInput::make('password')
                ->afterStateUpdated(fn(Livewire $livewire) => $livewire->validateOnly('data.password'))
                ->dehydrated(fn($state): bool => filled($state))
                ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                ->extraAttributes(['dir' => 'ltr'])
                ->hintAction(GeneratePasswordAction::make())
                ->label(__('vendra-user::attributes.password'))
                ->live(debounce: 500)
                ->password()
                ->required(fn(string $operation): bool => 'create' === $operation)
                ->revealable(filament()->arePasswordsRevealable())
                ->rule(Password::default()),

            Select::make('roles')
                ->afterStateUpdated(fn(Livewire $livewire) => $livewire->validateOnly('data.roles'))
                ->label(__('vendra-permission::navigation.role'))
                ->live()
                ->multiple()
                ->native(false)
                ->preload()
                ->relationship('roles', 'name')
                ->searchable(),

            Select::make('permissions')
                ->afterStateUpdated(fn(Livewire $livewire) => $livewire->validateOnly('data.permissions'))
                ->label(__('vendra-permission::navigation.permission'))
                ->live()
                ->multiple()
                ->native(false)
                ->preload()
                ->relationship('permissions', 'name')
                ->searchable(),
        ];

        if (TagIntegration::isAvailable()) {
            $components[] = SpatieTagsInput::make('tags')
                ->afterStateUpdated(fn(Livewire $livewire) => $livewire->validateOnly('data.tags'))
                ->columnSpanFull()
                ->label(__('vendra-support::attributes.tags'))
                ->live()
                ->type(\Misaf\VendraUser\Models\User::TAG_TYPE);
        }

        return $schema
            ->components($components);
    }

}
