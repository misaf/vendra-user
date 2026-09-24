<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Misaf\VendraUser\Actions\UpdateUserPasswordAction;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\UserResource;
use Misaf\VendraUser\Models\User;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
            DeleteAction::make(),
        ];
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
