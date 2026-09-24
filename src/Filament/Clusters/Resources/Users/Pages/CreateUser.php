<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Misaf\VendraUser\Actions\CreateUserAction;
use Misaf\VendraUser\Filament\Clusters\Resources\Users\UserResource;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getBreadcrumb(): string
    {
        return self::$breadcrumb ?? __('filament-panels::resources/pages/create-record.breadcrumb').' '.__('vendra-user::navigation.user');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $user = resolve(CreateUserAction::class)->execute(
                tenant: Filament::getTenant(),
                username: Arr::string($data, 'username'),
                email: Arr::string($data, 'email'),
                password: Arr::string($data, 'password'),
                isVerified: false,
            );

            $emailVerifiedAt = Arr::get($data, 'email_verified_at');

            if (filled($emailVerifiedAt)) {
                $user->forceFill(['email_verified_at' => $emailVerifiedAt])->save();
            }

            return $user;
        });
    }
}
