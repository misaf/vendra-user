<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Misaf\VendraSupport\Support\TenantAwareness;
use Misaf\VendraUser\Models\User;

/**
 * @extends Factory<User>
 */
#[UseModel(User::class)]
final class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username'          => fake()->userName(),
            'email'             => fake()->unique()->email(),
            'email_verified_at' => Carbon::now(),
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
        ];
    }

    /**
     * No-op without a tenant provider, since there is no `tenant_id` column.
     */
    public function forTenant(Model|int $tenant): static
    {
        if ( ! TenantAwareness::enabled()) {
            return $this;
        }

        return $this->state(fn(): array => [
            'tenant_id' => $tenant instanceof Model ? $tenant->getKey() : $tenant,
        ]);
    }

    public function forReseller(int $resellerId): static
    {
        return $this->state(fn(): array => [
            'reseller_id' => $resellerId,
        ]);
    }

    public function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            $user->assignRole($role);
        });
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
