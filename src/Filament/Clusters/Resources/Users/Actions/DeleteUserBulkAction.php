<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Clusters\Resources\Users\Actions;

use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Misaf\VendraUser\Actions\DeleteUserAction;
use Misaf\VendraUser\Exceptions\LastAdministratorException;
use Misaf\VendraUser\Models\User;

final class DeleteUserBulkAction extends DeleteBulkAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->using(function (Collection|LazyCollection $records, DeleteUserAction $deleteUser): void {
            foreach ($records as $record) {
                if (! $record instanceof User) {
                    $this->reportBulkProcessingFailure();

                    continue;
                }

                try {
                    $deleteUser->execute($record) || $this->reportBulkProcessingFailure();
                } catch (LastAdministratorException) {
                    $this->reportBulkProcessingFailure(
                        'last_administrator',
                        message: fn (): string => __('vendra-user::forms.last_administrator_required'),
                    );
                }
            }
        });
    }
}
