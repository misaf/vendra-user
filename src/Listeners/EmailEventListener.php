<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Listeners;

use App\DataTransferObjects\Email\EmailEventDto;
use App\Events\Email\EmailBouncedEvent;
use App\Events\Email\EmailComplainedEvent;
use App\Services\Email\EmailEventService;
use Closure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Misaf\VendraTenant\Scopes\TenantScope;
use Misaf\VendraUser\Models\User;
use Throwable;

final class EmailEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly EmailEventService $emailEventService,
    ) {}

    public function handleEmailBounced(EmailBouncedEvent $event): void
    {
        $this->processEmails(
            emails: $event->eventData->to,
            callback: fn(string $email) => $this->bouncedEmail($email, $event->eventData),
            eventName: 'EmailBounced',
        );
    }

    public function handleEmailComplained(EmailComplainedEvent $event): void
    {
        $this->processEmails(
            emails: $event->eventData->to,
            callback: fn(string $email) => $this->complainedEmail($email, $event->eventData),
            eventName: 'EmailComplained',
        );
    }

    /**
     * @param list<string> $emails
     * @param Closure $callback
     * @param string $eventName
     * @return void
     */
    private function processEmails(array $emails, Closure $callback, string $eventName): void
    {
        if (empty($emails)) {
            Log::warning("{$eventName} event received without valid email data.");
            return;
        }

        foreach ($emails as $email) {
            try {
                $callback($email);
            } catch (Throwable $e) {
                Log::error("Failed to process {$eventName} for email", [
                    'email'    => $email,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    }

    private function bouncedEmail(string $email, EmailEventDto $eventData): void
    {
        if ($this->emailEventService->isHardBounce($eventData)) {
            $this->revokeEmailVerification($email);

        } elseif ($this->emailEventService->isSoftBounce($eventData)) {
            Log::info('Soft bounce recorded for user', ['email' => $email]);

        } else {
            Log::warning('Unknown bounce type, treating as hard bounce', [
                'email'       => $email,
                'bounce_type' => $eventData->bounce?->type,
            ]);
        }
    }

    private function complainedEmail(string $email, EmailEventDto $eventData): void
    {
        if ($this->emailEventService->isComplaint($eventData)) {
            $this->revokeEmailVerification($email);

        } else {
            Log::warning('Unknown complaint type, treating as Complained', [
                'email'       => $email,
                'bounce_type' => $eventData->bounce?->type,
            ]);
        }
    }

    private function revokeEmailVerification(string $email): void
    {
        $user = User::withoutGlobalScope(TenantScope::class)
            ->where('email', $email)
            ->whereNotNull('email_verified_at')
            ->first();

        if (null === $user) {
            return;
        }

        $user->update(['email_verified_at' => null]);
        Log::info('Email verification revoked.', [
            'email' => $email,
        ]);
    }
}
