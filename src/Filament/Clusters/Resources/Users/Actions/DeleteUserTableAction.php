<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Actions;

use Filament\Actions\DeleteAction;
use Misaf\VendraUser\Actions\DeleteUserAction;
use Misaf\VendraUser\Exceptions\LastAdministratorException;
use Misaf\VendraUser\Models\User;

final class DeleteUserTableAction extends DeleteAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->failureNotificationTitle(__('vendra-user::forms.last_administrator_required'))
            ->using(function (User $record, DeleteUserAction $deleteUser): bool {
                try {
                    return $deleteUser->execute($record);
                } catch (LastAdministratorException) {
                    return false;
                }
            });
    }
}
