<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Misaf\VendraSupport\Tenancy\Database\Seeders\DemoContentSeeder as BaseDemoContentSeeder;
use Misaf\VendraUser\Actions\CreateUserAction;
use Misaf\VendraUser\Database\Factories\UserFactory;
use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Support\PasswordGenerator;

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
     * Seed the fixtures idempotently, keyed on email within the current tenant.
     *
     * Store provisioning retries the whole seed list, so a partial run must be repeatable.
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
        if (User::query()->where('email', Arr::string($data, 'email'))->exists()) {
            return;
        }

        $this->createUserAction->execute(
            tenant: $tenant,
            username: Arr::string($data, 'username'),
            email: Arr::string($data, 'email'),
            password: PasswordGenerator::generate(),
            role: Arr::has($data, 'role') ? Arr::string($data, 'role') : null,
            isVerified: ! Arr::has($data, 'email_verified_at') || Arr::get($data, 'email_verified_at') !== null,
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
