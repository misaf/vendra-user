<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class EmailValidation implements ValidationRule
{
    /** @var list<string> */
    private array $allowedDomains;

    public function __construct(private bool $isProduction = true)
    {
        $raw = Config::array('services.email.allowed_domains');

        $stringsOnly = array_filter(
            $raw,
            static fn($item): bool => is_string($item),
        );

        $this->allowedDomains = array_values($stringsOnly);
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value)) {
            $email = (string) $value;
        } else {
            $fail($attribute);
            return;
        }

        $domain = $this->getEmailHost($email);

        if ( ! $this->isAllowedDomain($domain)) {
            Log::error('Email domain not allowed.', ['attribute' => $attribute, 'email' => $email, 'domain' => $domain]);
            $fail(__('validation.custom.email.domain_not_allowed', [
                'domain'  => $domain,
                'allowed' => implode(', ', $this->allowedDomains),
            ]));

            return;
        }

        // Only perform email validation in production
        if ( ! $this->isProduction) {
            return;
        }

        try {
            $payload = $this->fetchEmailValidationData($email);

            if (empty($payload) || ! $this->isDeliverable($payload)) {
                Log::error('Email undeliverable or API unavailable.', ['email' => $email, 'response' => $payload]);
                $fail(__('validation.custom.email.undeliverable'));
            }
        } catch (ConnectionException $e) {
            Log::error('Email validation API connection timeout.', ['exception' => $e]);
            $fail(__('validation.custom.email.service_unavailable'));
        } catch (RequestException $e) {
            Log::error('Email validation API request error.', ['exception' => $e]);
            $fail(__('validation.custom.email.server_error'));
        } catch (Throwable $e) {
            Log::error('Error', ['exception' => $e]);
            $fail(__('validation.custom.email.server_error'));
        }
    }

    private function getEmailHost(string $email): string
    {
        // Str::after will return everything after the first "@"
        return Str::lower(Str::after($email, '@'));
    }

    private function fetchEmailValidationData(string $email): mixed
    {
        $host = Config::string('services.emailable.host');
        $apiKey = Config::string('services.emailable.api_key');

        $response = Http::timeout(6)
            ->retry(2, 100)
            ->get($host, [
                'api_key' => $apiKey,
                'email'   => $email,
            ]);

        return $response->ok() ? $response->json() : null;
    }

    private function isDeliverable(mixed $data): bool
    {
        if ( ! is_array($data)) {
            return false;
        }

        return isset($data['state']) && 'undeliverable' !== $data['state'];
    }

    private function isAllowedDomain(string $domain): bool
    {
        return in_array($domain, $this->allowedDomains, true);
    }
}
