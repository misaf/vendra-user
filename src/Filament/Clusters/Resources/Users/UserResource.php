<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users;

use App\Filament\Admin\Resources\ActivityLogs\RelationManagers\ActivityLogRelationManager;
use App\Filament\Admin\Resources\AuthifyLogs\RelationManagers\AuthifyLogRelationManager;
use App\Filament\Admin\Resources\Tags\Actions\AddTagAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Clusters\Cluster;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\SpatieTagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component as Livewire;
use Misaf\VendraAffiliate\Filament\Clusters\Resources\Affiliates\RelationManagers\AffiliateRelationManager;
use Misaf\VendraSupport\Support\TenantAwareness;
use Misaf\VendraTransaction\Enums\TransactionStatusEnum;
use Misaf\VendraTransaction\Enums\TransactionTypeEnum;
use Misaf\VendraTransaction\Facades\TransactionService;
use Misaf\VendraTransaction\Filament\Clusters\Resources\Transactions\RelationManagers\TransactionLimitRelationManager;
use Misaf\VendraTransaction\Filament\Clusters\Resources\Transactions\RelationManagers\TransactionRelationManager;
use Misaf\VendraUser\Facades\UserService;
use Misaf\VendraUser\Filament\Clusters\Resources\UserLevelHistories\RelationManagers\UserLevelHistoryRelationManager;
use Misaf\VendraUser\Filament\Clusters\Resources\UserProfiles\RelationManagers\UserProfileRelationManager;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\CreateUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\EditUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\ListUsers;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\ViewUser;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\RelationManagers\UserMessengerRelationManager;
use Misaf\VendraUser\Filament\Clusters\UsersCluster;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Rules\EmailValidation;
use Mokhosh\FilamentRating\Columns\RatingColumn;
use Mokhosh\FilamentRating\Components\Rating;

final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'username';

    protected static ?string $slug = 'users';

    /**
     * @var class-string<Cluster>|null
     */
    protected static ?string $cluster = UsersCluster::class;

    public static function getBreadcrumb(): string
    {
        return __('vendra-user::navigation.user');
    }

    public static function getModelLabel(): string
    {
        return __('vendra-user::navigation.user');
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.user_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('vendra-user::navigation.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('vendra-user::navigation.user');
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['tags']);
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['username', 'email', 'tags.name'];
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('vendra-user::attributes.email')      => $record->email,
            __('vendra-permission::navigation.role') => Arr::join(
                $record->roles()->pluck('name')->toArray(),
                ', ',
            ),
            __('vendra-tagger::navigation.tag') => new HtmlString(
                "<span dir='ltr'>" . collect($record->tags->pluck('name'))
                    ->map(fn($tag) => "#{$tag}")
                    ->implode(' ')
                . '</span>',
            ),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::rememberForever('user-row-count', fn() => (string) Number::format(self::getModel()::count()));
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view'   => ViewUser::route('/{record}'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<class-string<RelationManager>|RelationGroup|RelationManagerConfiguration>
     */
    public static function getRelations(): array
    {
        return [
            // UserProfileRelationManager::class,
            // AffiliateRelationManager::class,
            // RelationGroup::make(__('vendra-transaction::navigation.transaction'), [
            //     TransactionRelationManager::class,
            //     TransactionLimitRelationManager::class,
            // ]),
            // UserLevelHistoryRelationManager::class,
            // UserMessengerRelationManager::class,
            // AuthifyLogRelationManager::class,
            // ActivityLogRelationManager::class,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Rating::make('rating')
                //     ->label(__('user::attributes.rating'))
                //     ->size('md')
                //     ->dehydrated(false)
                //     ->columnSpanFull(),

                TextInput::make('username')
                    ->afterStateUpdated(fn(Livewire $livewire) => $livewire->validateOnly('data.username'))
                    ->autofocus()
                    ->dehydrated(fn(string $operation) => 'create' === $operation)
                    ->disabledOn('edit')
                    ->extraAttributes(['dir' => 'ltr'])
                    ->hint('Letters, dashes, and underscores are allowed')
                    ->label(__('vendra-user::attributes.username'))
                    ->live(onBlur: true)
                    ->maxLength(12)
                    ->minLength(3)
                    ->required()
                    ->rules('alpha_dash')
                    ->unique(modifyRuleUsing: fn(Unique $rule) => $rule->withoutTrashed()),

                TextInput::make('email')
                    ->afterStateUpdated(fn(Livewire $livewire) => $livewire->validateOnly("data.email"))
                    ->autofocus()
                    ->columnSpan(['lg' => 2])
                    ->email()
                    ->label(__('vendra-user::attributes.email'))
                    ->live(onBlur: true)
                    ->maxLength(255)
                    ->required()
                    ->rules(['bail', 'email:rfc,strict,spoof,filter,filter_unicode', new EmailValidation(app()->isProduction())])
                    ->unique(
                        modifyRuleUsing: fn(Unique $rule) => TenantAwareness::constrainUniqueRule($rule),
                    )
                    ->extraAttributes(['dir' => 'ltr']),

                DateTimePicker::make('email_verified_at')
                    ->closeOnDateSelection()
                    ->displayFormat('Y-m-d H:i')
                    ->firstDayOfWeek(6)
                    ->unless(app()->isLocale('fa'), fn(DateTimePicker $column) => $column->jalali())
                    ->label(__('vendra-user::attributes.email_verified_at'))
                    ->maxDate(now())
                    ->native(false)
                    ->seconds(false),

                TextInput::make('password')
                    ->dehydrated(fn($state): bool => filled($state))
                    ->dehydrateStateUsing(fn($state): string => Hash::make($state))
                    ->extraAttributes(['dir' => 'ltr'])
                    ->hintAction(
                        Action::make('copyCostToPrice')
                            ->label(__('vendra-user::forms.random_password'))
                            ->icon('heroicon-o-shield-check')
                            ->disabled(function (string $operation) {
                                return 'view' === $operation;
                            })
                            ->iconPosition(function () {
                                if ('fa' === app()->getLocale()) {
                                    return IconPosition::After;
                                }

                                return IconPosition::Before;
                            })
                            ->action(function (Set $set): void {
                                $set('password', UserService::generatePassword(10));
                            }),
                    )
                    ->label(__('vendra-user::attributes.password'))
                    ->live(debounce: 500)
                    ->password()
                    ->required(fn(string $operation): bool => 'create' === $operation)
                    ->revealable(filament()->arePasswordsRevealable())
                    ->rule(Password::default()),

                Select::make('role')
                    ->label(__('vendra-permission::navigation.role'))
                    ->multiple()
                    ->native(false)
                    ->preload()
                    ->relationship('roles', 'name')
                    ->searchable(),

                Select::make('permission')
                    ->label(__('vendra-permission::navigation.permission'))
                    ->multiple()
                    ->native(false)
                    ->preload()
                    ->relationship('permissions', 'name')
                    ->searchable(),

                SpatieTagsInput::make('tags')
                    ->label(__('vendra-tagger::navigation.tag')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row')
                    ->label('#')
                    ->rowIndex(),

                // RatingColumn::make('rating')
                //     ->label(__('user::attributes.rating'))
                //     ->size('md'),

                SpatieMediaLibraryImageColumn::make('latestUserProfile.image')
                    ->circular()
                    ->conversion('thumb-table')
                    ->defaultImageUrl(url('coin-payment/images/default.png'))
                    ->extraImgAttributes(['class' => 'saturate-50', 'loading' => 'lazy'])
                    ->label(__('vendra-user::attributes.image'))
                    ->stacked(),
                TextColumn::make('username')
                    ->label(__('vendra-user::attributes.username'))
                    ->searchable(isGlobal: true),

                TextColumn::make('email')
                    ->label(__('vendra-user::attributes.email'))
                    ->searchable(isGlobal: true),

                TextColumn::make('roles.name')
                    ->badge()
                    ->label(__('vendra-permission::navigation.role'))
                    ->separator(','),

                // SpatieTagsColumn::make('tags')
                //     ->label(__('vendra-tagger::navigation.tag'))
                //     ->action(AddTagAction::make()),

                TextColumn::make('email_verified_at'),

                TextColumn::make('created_at')
                    ->label(__('vendra-user::attributes.created_at')),

                TextColumn::make('updated_at')
                    ->label(__('vendra-user::attributes.updated_at')),

                TextColumn::make('deleted_at'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('agent')
                        ->action(function (User $record): void {
                            self::storeAgent($record);
                        })
                        ->color('gray')
                        ->icon('heroicon-s-building-storefront')
                        ->label(__('vendra-affiliate::navigation.affiliate'))
                        ->requiresConfirmation()
                        ->hidden(function (User $record): bool {
                            return self::isAgent($record);
                        }),

                    ViewAction::make(),

                    EditAction::make(),

                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('store_transaction')
                        ->schema([
                            Select::make('transaction_type')
                                ->columnSpanFull()
                                ->label(__('vendra-transaction::attributes.transaction_type'))
                                ->native(false)
                                ->options(TransactionTypeEnum::class)
                                ->required(),
                            TextInput::make('amount')
                                ->autocomplete(false)
                                ->columnSpanFull()
                                ->extraInputAttributes(['dir' => 'ltr'])
                                ->label(__('vendra-transaction::attributes.amount'))
                                ->minValue(1)
                                ->numeric()
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            DB::transaction(function () use ($data, $records): void {
                                $records->each(function (User $record) use ($data): void {
                                    $transactionType = TransactionTypeEnum::from($data['transaction_type']);
                                    $amount = TransactionTypeEnum::Withdrawal === $transactionType
                                        ? -abs((int) $data['amount'])
                                        : abs((int) $data['amount']);

                                    TransactionService::createTransaction(
                                        transactionGateway: 'internal-transactions',
                                        user: $record,
                                        transactionType: $transactionType,
                                        amount: $amount,
                                        status: TransactionStatusEnum::Pending,
                                    );
                                });

                                Notification::make()->success()->title(__('vendra-user::messages.transaction_created_successfully'))->send();
                            });
                        })
                        ->label('create')
                        ->icon('heroicon-s-user')
                        ->deselectRecordsAfterCompletion()
                        ->sendSuccessNotification(),
                ])->label(__('vendra-user::forms.actions')),
                BulkActionGroup::make([
                    BulkAction::make('store_affiliate')
                        ->action(function (Collection $records): void {
                            $records->each(function (User $record): void {
                                if (self::isAgent($record)) {
                                    return;
                                }

                                self::storeAgent($record);
                            });
                        })
                        ->label('تبدیل به نماینده')
                        ->icon('heroicon-s-user')
                        ->deselectRecordsAfterCompletion(),
                ])->label(__('vendra-affiliate::navigation.affiliate')),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function storeAgent(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->affiliates()
                ->create([
                    'commission_percent' => 20,
                    'status'             => true,
                ]);

            $user->assignRole('reseller');
        }, 5);
    }

    private static function isAgent(User $user): bool
    {
        return $user->affiliates()
            ->where('status', true)
            ->exists();
    }
}
