<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users;

use App\Filament\Admin\Resources\ActivityLogs\RelationManagers\ActivityLogRelationManager;
use App\Filament\Admin\Resources\AuthifyLogs\RelationManagers\AuthifyLogRelationManager;
use App\Filament\Admin\Resources\Tags\Actions\AddTagAction;
use App\Forms\Components\WysiwygEditor;
use App\Jobs\SendBulkAdMailerJob;
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
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Size;
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
use Misaf\VendraTenant\Models\Tenant;
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
        return __('navigation.user');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.user');
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.user_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.user');
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
            __('form.email') => $record->email,
            __('model.role') => Arr::join(
                $record->roles()->pluck('name')->toArray(),
                ', ',
            ),
            __('tag::navigation.tag') => new HtmlString(
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
                    ->label(__('form.username'))
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
                    ->label(__('form.email'))
                    ->live(onBlur: true)
                    ->maxLength(255)
                    ->required()
                    ->rules(['bail', 'email:rfc,strict,spoof,filter,filter_unicode', new EmailValidation(app()->isProduction())])
                    ->unique(
                        modifyRuleUsing: fn(Unique $rule) => $rule->where('tenant_id', Tenant::current()->id),
                    )
                    ->extraAttributes(['dir' => 'ltr']),

                DateTimePicker::make('email_verified_at')
                    ->closeOnDateSelection()
                    ->displayFormat('Y-m-d H:i')
                    ->firstDayOfWeek(6)
                    ->unless(app()->isLocale('fa'), fn(DateTimePicker $column) => $column->jalali())
                    ->label(__('form.email_verified_at'))
                    ->maxDate(now())
                    ->native(false)
                    ->seconds(false),

                TextInput::make('password')
                    ->dehydrated(fn($state): bool => filled($state))
                    ->dehydrateStateUsing(fn($state): string => Hash::make($state))
                    ->extraAttributes(['dir' => 'ltr'])
                    ->hintAction(
                        Action::make('copyCostToPrice')
                            ->label(__('Random Password'))
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
                    ->label(__('form.password'))
                    ->live(debounce: 500)
                    ->password()
                    ->required(fn(string $operation): bool => 'create' === $operation)
                    ->revealable(filament()->arePasswordsRevealable())
                    ->rule(Password::default()),

                Select::make('role')
                    ->label(__('model.role'))
                    ->multiple()
                    ->native(false)
                    ->preload()
                    ->relationship('roles', 'name')
                    ->searchable(),

                Select::make('permission')
                    ->label(__('model.permission'))
                    ->multiple()
                    ->native(false)
                    ->preload()
                    ->relationship('permissions', 'name')
                    ->searchable(),

                SpatieTagsInput::make('tags')
                    ->label(__('tag::navigation.tag')),
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
                    ->label(__('form.image'))
                    ->stacked(),
                TextColumn::make('username')
                    ->label(__('form.username'))
                    ->searchable(isGlobal: true),

                TextColumn::make('email')
                    ->label(__('form.email'))
                    ->searchable(isGlobal: true),

                TextColumn::make('roles.name')
                    ->badge()
                    ->label(__('model.role'))
                    ->separator(','),

                // SpatieTagsColumn::make('tags')
                //     ->label(__('tag::navigation.tag'))
                //     ->action(AddTagAction::make()),

                TextColumn::make('email_verified_at'),

                TextColumn::make('created_at')
                    ->label(__('form.created_at')),

                TextColumn::make('updated_at')
                    ->label(__('form.updated_at')),

                TextColumn::make('deleted_at'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                Action::make('create')
                    ->color('gray')
                    ->icon('heroicon-o-envelope')
                    ->label(__('ارسال تبلیغات'))
                    ->size(Size::Small)
                    ->steps([
                        Step::make('content')
                            ->description(__('عنوان و متن محتوا'))
                            ->label(__('محتوا'))
                            ->schema([
                                TextInput::make('subject')
                                    ->label(__('عنوان'))
                                    ->required(),
                                // WysiwygEditor::make('description'),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        SendBulkAdMailerJob::dispatch(
                            subject: $data['subject'],
                            description: $data['description'],
                        );
                    })
                    ->slideOver(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('agent')
                        ->action(function (User $record): void {
                            self::storeAgent($record);
                        })
                        ->color('gray')
                        ->icon('heroicon-s-building-storefront')
                        ->label(__('model.affiliate'))
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
                                ->label(__('form.category'))
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

                                Notification::make()->success()->title('Transaction created successfully')->send();
                            });
                        })
                        ->label('create')
                        ->icon('heroicon-s-user')
                        ->deselectRecordsAfterCompletion()
                        ->sendSuccessNotification(),
                ])->label(__('navigation.x')),
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
                ])->label(__('navigation.affiliate')),
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
