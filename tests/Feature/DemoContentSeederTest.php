<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Misaf\VendraUser\Database\Seeders\DemoContentSeeder;
use Misaf\VendraUser\Models\User;

it('seeds its demo fixtures again without duplicating rows', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    makeCurrentTestTenant();

    // The fixtures assign the `user` role the permission module seeds.
    /** @var class-string<Model> $roleModel */
    $roleModel = Config::string('permission.models.role');
    $roleModel::query()->create(['name' => 'user', 'guard_name' => 'web']);

    resolve(DemoContentSeeder::class)->run();

    $users = User::query()->count();

    expect($users)->toBeGreaterThan(0);

    resolve(DemoContentSeeder::class)->run();

    expect(User::query()->count())->toBe($users);
});
