<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Misaf\VendraUser\Actions\DemoteTenantAdministratorAction;
use Misaf\VendraUser\Actions\UpdateUserPasswordAction;
use Misaf\VendraUser\Exceptions\LastAdministratorException;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Actions\DeleteUserTableAction;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages\Concerns\EnforcesStaffLimit;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\UserResource;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\TenantAdministratorGuard;

final class EditUser extends EditRecord
{
    use EnforcesStaffLimit;

    protected static string $resource = UserResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    private bool $becomesStaff = false;

    public function getBreadcrumb(): string
    {
        return self::$breadcrumb ?? __('filament-panels::resources/pages/edit-record.breadcrumb').' '.__('vendra-user::navigation.user');
    }

    /**
     * @return array<int, ViewAction|DeleteAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteUserTableAction::make(),
        ];
    }

    /**
     * @throws Halt
     */
    protected function beforeSave(): void
    {
        $record = $this->getRecord();
        $this->becomesStaff = $this->assignsRoles() && $record instanceof User && $record->roles()->doesntExist();

        if ($record instanceof User && ($tenant = $record->tenant()->first()) !== null) {
            $guard = resolve(TenantAdministratorGuard::class);
            try {
                $guard->execute($tenant, function () use ($guard, $record, $tenant): void {
                    $record->refreshForUpdate();
                    $this->becomesStaff = $this->assignsRoles() && $record->roles()->doesntExist();
                    $selectedRoles = collect(Arr::wrap(Arr::get($this->data ?? [], 'roles')));

                    if ($record->hasRole($guard->roleName()) && $selectedRoles->doesntContain($guard->role()->getKey())) {
                        resolve(DemoteTenantAdministratorAction::class)->execute($tenant, $record);
                    }
                });
            } catch (LastAdministratorException) {
                Notification::make()->danger()->title(__('vendra-user::forms.last_administrator_required'))->send();

                throw (new Halt)->rollBackDatabaseTransaction();
            }
        }

        if ($this->becomesStaff) {
            $this->assertRoomForStaff();
        }
    }

    protected function afterSave(): void
    {
        if ($this->becomesStaff) {
            $this->recordStaffAdded();
        }
    }

    /**
     * @param  User  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $password = Arr::get($data, 'password');
        $attributes = array_diff_key($data, ['password' => true]);

        return DB::transaction(function () use ($record, $attributes, $password): Model {
            $record->update($attributes);

            if (is_string($password) && $password !== '') {
                resolve(UpdateUserPasswordAction::class)->execute($record, $password);
            }

            return $record;
        });
    }
}
