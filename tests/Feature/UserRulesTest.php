<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Misaf\VendraUser\Support\UserRules;

function withPasswordPolicy(Password $policy, Closure $assertions): void
{
    $passwordDefaults = Password::$defaultCallback;
    Password::defaults(fn (): Password => $policy);

    try {
        $assertions();
    } finally {
        Password::$defaultCallback = $passwordDefaults;
    }
}

it('generates a password that passes the shared password rules', function (Password $policy): void {
    withPasswordPolicy($policy, function (): void {
        $password = UserRules::generatePassword();

        expect(Validator::make(['password' => $password], ['password' => UserRules::password()])->passes())->toBeTrue();
    });
})->with([
    'default' => fn (): Password => Password::min(8),
    'longer than the generated length' => fn (): Password => Password::min(64),
    'symbols' => fn (): Password => Password::min(8)->symbols(),
    'mixed case and numbers' => fn (): Password => Password::min(12)->mixedCase()->numbers(),
]);

it('generates a password within the configured length bounds', function (): void {
    withPasswordPolicy(Password::min(8)->max(10), function (): void {
        expect(UserRules::generatePassword())->toHaveLength(10);
    });

    withPasswordPolicy(Password::min(64), function (): void {
        expect(UserRules::generatePassword())->toHaveLength(64);
    });

    withPasswordPolicy(Password::min(8), function (): void {
        expect(UserRules::generatePassword())->toHaveLength(UserRules::PASSWORD_LENGTH);
    });
});

it('omits symbols unless the password policy requires them', function (): void {
    withPasswordPolicy(Password::min(8), function (): void {
        expect(UserRules::generatePassword())->toMatch('/^[\pL\pN]+$/u');
    });

    withPasswordPolicy(Password::min(8)->symbols(), function (): void {
        expect(UserRules::generatePassword())->toMatch('/[^\pL\pN]/u');
    });
});
