<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraUser\Models\User;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'tenant_id'         => Tenant::factory(),
            'username'          => fake()->userName(),
            'email'             => fake()->unique()->email(),
            'email_verified_at' => Carbon::now(),
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
        ];
    }

    public function forTenant(Tenant|int $tenant): static
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $this->state(fn(): array => ['tenant_id' => $tenantId]);
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
