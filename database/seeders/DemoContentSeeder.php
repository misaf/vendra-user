<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Misaf\VendraSupport\Tenancy\Database\Seeders\DemoContentSeeder as BaseDemoContentSeeder;
use Misaf\VendraSupport\Tenancy\RequiresCurrentTenant;
use Misaf\VendraUser\Actions\CreateUserAction;
use Misaf\VendraUser\Database\Factories\UserFactory;

final class DemoContentSeeder extends BaseDemoContentSeeder
{
    use RequiresCurrentTenant;

    public function __construct(private readonly CreateUserAction $createUserAction) {}

    protected function seedFactories(): void
    {
        $tenant = $this->currentTenant();

        $this->seedFactoryRecords($tenant);
    }

    /**
     * @param list<array<string, mixed>> $records
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
     * @param array<string, mixed> $record
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
        $this->createUserAction->execute(
            tenant: $tenant,
            username: $data['username'],
            email: $data['email'],
            password: Str::password(32),
            role: $data['role'] ?? null,
            isVerified: ! array_key_exists('email_verified_at', $data) || null !== $data['email_verified_at'],
        );
    }

    /**
     * @param array<string, mixed> $record
     *
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
                'username'          => ['required', 'string'],
                'email'             => ['required', 'email'],
                'email_verified_at' => ['nullable', 'date'],
                'role'              => ['sometimes', 'string'],
            ],
        )->validate();

        return $validated;
    }

}
