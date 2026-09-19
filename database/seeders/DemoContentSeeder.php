<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Misaf\VendraSupport\Tenancy\Database\Seeders\DemoContentSeeder as BaseDemoContentSeeder;
use Misaf\VendraUser\Actions\CreateUserAction;
use Misaf\VendraUser\Database\Factories\UserFactory;
use Misaf\VendraUser\Models\User;

final class DemoContentSeeder extends BaseDemoContentSeeder
{
    protected const array FACTORIES = [UserFactory::class];

    public function __construct(private readonly CreateUserAction $createUserAction) {}

    protected function seedFactories(): void
    {
        $tenant = $this->currentTenant();

        $this->seedFactoryRecords($tenant);
    }

    /**
     * The email address is the natural key — it already carries a
     * tenant-scoped unique index — so an already seeded user is left alone
     * rather than created a second time with a fresh random password. The
     * seed command makes the tenant current for the run, so the lookup is
     * scoped to it. Store provisioning retries the whole seed list on
     * failure, so a partial run has to be safe to repeat.
     *
     * @param  list<array<string, mixed>>  $records
     */
    protected function seedFixtures(array $records): void
    {
        $tenant = $this->currentTenant();

        foreach ($records as $record) {
            $this->seedFixtureRecord($tenant, $record);
        }
    }

    protected function seedFactoryRecords(Model $tenant): void
    {
        UserFactory::new()
            ->forTenant($tenant)
            ->count(2)
            ->create();

        UserFactory::new()
            ->forTenant($tenant)
            ->unverified()
            ->createOne();
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected function seedFixtureRecord(Model $tenant, array $record): void
    {
        $data = $this->validatedFixtureRecord($record);

        $this->handleSeedFixtureRecord($tenant, $data);
    }

    /**
     * @param array{
     *     username: string,
     *     email: string,
     *     email_verified_at?: string|null,
     *     role?: string
     * } $data
     */
    private function handleSeedFixtureRecord(Model $tenant, array $data): void
    {
        if (User::query()->where('email', Arr::get($data, 'email'))->exists()) {
            return;
        }

        $this->createUserAction->execute(
            tenant: $tenant,
            username: Arr::get($data, 'username'),
            email: Arr::get($data, 'email'),
            password: Str::password(32),
            role: Arr::get($data, 'role', null),
            isVerified: ! array_key_exists('email_verified_at', $data) || Arr::get($data, 'email_verified_at') !== null,
        );
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{
     *     username: string,
     *     email: string,
     *     email_verified_at?: string|null,
     *     role?: string
     * }
     */
    private function validatedFixtureRecord(array $record): array
    {
        /** @var array{
         *     username: string,
         *     email: string,
         *     email_verified_at?: string|null,
         *     role?: string
         * } $validated
         */
        $validated = Validator::make(
            data: $record,
            rules: [
                'username' => ['required', 'string'],
                'email' => ['required', 'email'],
                'email_verified_at' => ['nullable', 'date'],
                'role' => ['sometimes', 'string'],
            ],
        )->validate();

        return $validated;
    }
}
