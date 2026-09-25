<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Filament\Pages\Auth;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Misaf\VendraUser\Actions\UpdateUserPasswordAction;
use Misaf\VendraUser\Models\User;
use SensitiveParameter;

/**
 * Filament's default profile edits a `name` users do not have and hashes the
 * password outside vendra-user. The username and email are shown read-only:
 * each panel changes them through its own actions.
 */
final class EditProfile extends \Filament\Auth\Pages\EditProfile
{
    private ?string $newPassword = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->label(__('vendra-user::attributes.username'))
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('email')
                    ->label(__('vendra-user::attributes.email'))
                    ->disabled()
                    ->dehydrated(false),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        $component = parent::getPasswordFormComponent();

        return $component instanceof TextInput
            ? $component->dehydrateStateUsing(fn (#[SensitiveParameter] ?string $state): ?string => $state)
            : $component;
    }

    /**
     * Remove the password from the data, so Filament does not write it itself.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(#[SensitiveParameter] array $data): array
    {
        $password = Arr::get($data, 'password', null);
        $this->newPassword = is_string($password) && $password !== '' ? $password : null;

        unset($data['password']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        if (! $record instanceof User || $this->newPassword === null) {
            return $record;
        }

        $user = resolve(UpdateUserPasswordAction::class)->execute($record, $this->newPassword);
        $this->newPassword = null;

        // Keep this session signed in: AuthenticateSession compares against the stored hash.
        if (request()->hasSession()) {
            request()->session()->put('password_hash_'.Filament::getAuthGuard(), $user->getAuthPassword());
        }

        return $user;
    }
}
