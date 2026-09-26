<?php

declare(strict_types=1);

namespace Storefront\Security\RateLimiter;

use Psr\Clock\ClockInterface;
use Shared\Domain\Exception\VerificationCodeRequestedTooRecentlyException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class VerificationCodeResendFlow
{
    private const string DOMAIN = 'verification_code';

    public function __construct(
        private VerificationCodeRateLimiter $rateLimiter,
        private ClockInterface $clock,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param callable(): void $dispatch dispatches the command; may throw a {@see VerificationCodeRequestedTooRecentlyException}
     */
    public function attempt(Request $request, string $key, string $purpose, callable $dispatch): void
    {
        $retryAt = $this->rateLimiter->consume($request, $key, $purpose);
        if (null !== $retryAt) {
            $minutes = (int) ceil(($retryAt->getTimestamp() - $this->clock->now()->getTimestamp()) / 60);
            $this->flash($request, 'error', 'flash_rate_limited', ['%minutes%' => $minutes]);

            return;
        }

        try {
            $dispatch();
        } catch (VerificationCodeRequestedTooRecentlyException $e) {
            $seconds = max(1, $e->retryAt->getTimestamp() - $this->clock->now()->getTimestamp());
            $this->flash($request, 'error', 'flash_too_recent', ['%seconds%' => $seconds]);

            return;
        }

        $this->flash($request, 'success', 'flash_sent');
    }

    public function reset(Request $request, string $key, string $purpose): void
    {
        $this->rateLimiter->reset($request, $key, $purpose);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function flash(Request $request, string $type, string $id, array $parameters = []): void
    {
        $session = $request->getSession();
        \assert($session instanceof FlashBagAwareSessionInterface);

        $session->getFlashBag()->add($type, $this->translator->trans($id, $parameters, domain: self::DOMAIN));
    }
}
