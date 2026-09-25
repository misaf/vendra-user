<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component as Livewire;
use Misaf\VendraSupport\Capabilities\TagIntegration;
use Misaf\VendraSupport\Contracts\TenantEntitlements;
use Misaf\VendraSupport\Enums\PlanLimit;
use Misaf\VendraSupport\Exceptions\EntitlementExceededException;
use Misaf\VendraSupport\Filament\Actions\GeneratePasswordAction;
use Misaf\VendraSupport\Tenancy\TenantAwareness;
use Misaf\VendraTagger\Filament\Forms\Components\ModelTagsInput;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\UserRules;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $components = [
            TextInput::make('username')
                ->afterStateUpdated(fn (Livewire $livewire) => $livewire->validateOnly('data.username'))
                ->autofocus()
                ->dehydrated(fn (string $operation): bool => $operation === 'create')
                ->disabledOn('edit')
                ->extraAttributes(['dir' => 'ltr'])
                ->helperText(__('vendra-user::forms.username_helper_text'))
                ->label(__('vendra-user::attributes.username'))
                ->live(onBlur: true)
                ->maxLength(UserRules::USERNAME_MAX_LENGTH)
                ->minLength(UserRules::USERNAME_MIN_LENGTH)
                ->required()
                ->rules(UserRules::username())
                ->rule(fn (?User $record): Unique => UserRules::unique('username', TenantAwareness::currentId(), $record?->id)),

            TextInput::make('email')
                ->afterStateUpdated(fn (Livewire $livewire) => $livewire->validateOnly('data.email'))
                ->columnSpan(['lg' => 2])
                ->email()
                ->extraAttributes(['dir' => 'ltr'])
                ->label(__('vendra-user::attributes.email'))
                ->live(onBlur: true)
                ->maxLength(255)
                ->required()
                ->rules(UserRules::email())
                ->rule(fn (?User $record): Unique => UserRules::unique('email', TenantAwareness::currentId(), $record?->id)),

            DateTimePicker::make('email_verified_at')
                ->afterStateUpdated(fn (Livewire $livewire) => $livewire->validateOnly('data.email_verified_at'))
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
                ->afterStateUpdated(fn (Livewire $livewire) => $livewire->validateOnly('data.password'))
                ->dehydrated(fn ($state): bool => filled($state))
                ->extraAttributes(['dir' => 'ltr'])
                ->hintAction(GeneratePasswordAction::make())
                ->label(__('vendra-user::attributes.password'))
                ->live(debounce: 500)
                ->password()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->revealable(filament()->arePasswordsRevealable())
                ->rules(UserRules::password()),

            Select::make('roles')
                ->afterStateUpdated(fn (Livewire $livewire) => $livewire->validateOnly('data.roles'))
                ->disabled(fn (?User $record): bool => self::staffLimitReached($record))
                ->hint(fn (?User $record): ?string => self::staffLimitReached($record)
                    ? EntitlementExceededException::limitReached(
                        PlanLimit::StaffPerStore,
                        resolve(TenantEntitlements::class)->limit(PlanLimit::StaffPerStore) ?? 0,
                    )->getMessage()
                    : null)
                ->hintColor('danger')
                ->label(__('vendra-permission::navigation.role'))
                ->live()
                ->multiple()
                ->native(false)
                ->preload()
                ->relationship('roles', 'name')
                ->searchable(),

            Select::make('permissions')
                ->afterStateUpdated(fn (Livewire $livewire) => $livewire->validateOnly('data.permissions'))
                ->label(__('vendra-permission::navigation.permission'))
                ->live()
                ->multiple()
                ->native(false)
                ->preload()
                ->relationship('permissions', 'name')
                ->searchable(),
        ];

        if (TagIntegration::isAvailable()) {
            $components[] = ModelTagsInput::make()
                ->type(User::TAG_TYPE);
        }

        return $schema
            ->components($components);
    }

    /**
     * Whether giving this user a role would take the store past its staff limit.
     * A user who already holds a role is staff already and keeps the field.
     */
    private static function staffLimitReached(?User $record): bool
    {
        if ($record instanceof User && $record->roles()->exists()) {
            return false;
        }

        return ! resolve(TenantEntitlements::class)->canAdd(PlanLimit::StaffPerStore);
    }
}
