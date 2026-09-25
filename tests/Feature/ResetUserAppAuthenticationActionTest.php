<?php

declare(strict_types=1);

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Support\Facades\DB;
use Misaf\VendraUser\Actions\ResetUserAppAuthenticationAction;
use Misaf\VendraUser\Models\User;

it('stores the authenticator secret encrypted and never serializes it', function (): void {
    $app = AppAuthentication::make();
    $secret = $app->generateSecret();
    $user = User::factory()->create(['tenant_id' => null]);

    $app->saveSecret($user, $secret);
    $app->saveRecoveryCodes($user, $app->generateRecoveryCodes());

    $stored = DB::table('users')->where('id', $user->id)->value('app_authentication_secret');

    expect($stored)->not->toBe($secret)
        ->and($user->refresh()->app_authentication_secret)->toBe($secret)
        ->and($user->toArray())->not->toHaveKeys(['app_authentication_secret', 'app_authentication_recovery_codes']);
});

it('removes the authenticator app and recovery codes', function (): void {
    $app = AppAuthentication::make();
    $user = User::factory()->withAppAuthentication()->create(['tenant_id' => null]);
    $app->saveRecoveryCodes($user, $app->generateRecoveryCodes());

    resolve(ResetUserAppAuthenticationAction::class)->execute($user);

    $user->refresh();

    expect($user->hasAppAuthentication())->toBeFalse()
        ->and($user->app_authentication_recovery_codes)->toBeNull();
});
