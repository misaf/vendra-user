<?php

declare(strict_types=1);

use Misaf\VendraUser\Models\User;
use Misaf\VendraUser\Services\UserEmailService;

describe('UserEmailService::getEmailDomain', function (): void {
    it('correctly extracts email domains', function (): void {
        $service = new UserEmailService();

        // Test the getEmailDomain method with valid email formats
        $testCases = [
            'user@gmail.com'       => 'gmail.com',
            'admin@yahoo.com'      => 'yahoo.com',
            'test@hotmail.com'     => 'hotmail.com',
            'user@test@domain.com' => 'domain.com', // Multiple @ symbols
            '@domain.com'          => 'domain.com', // Starts with @
        ];

        foreach ($testCases as $email => $expectedDomain) {
            $user = new User();
            $user->email = $email;

            expect($service->getEmailDomain($user))->toBe($expectedDomain);
        }
    });

    it('handles empty and null emails', function (): void {
        $service = new UserEmailService();

        // Test cases that should return null
        $nullEmails = [
            '', // Empty string
        ];

        foreach ($nullEmails as $email) {
            $user = new User();
            $user->email = $email;

            expect($service->getEmailDomain($user))->toBeNull();
        }
    });

    it('handles null user email', function (): void {
        $service = new UserEmailService();

        // Create user without setting email (it will be null)
        $user = new User();

        expect($service->getEmailDomain($user))->toBeNull();
    });

    it('handles emails without @ symbol', function (): void {
        $service = new UserEmailService();

        // Test cases that should return null (invalid email formats)
        $invalidEmails = [
            'invalidemail', // No @ symbol
            'justtext', // Plain text
            'user.domain.com', // Missing @
        ];

        foreach ($invalidEmails as $email) {
            $user = new User();
            $user->email = $email;

            expect($service->getEmailDomain($user))->toBeNull();
        }
    });
});
