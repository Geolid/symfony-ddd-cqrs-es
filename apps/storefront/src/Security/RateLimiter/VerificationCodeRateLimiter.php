<?php

declare(strict_types=1);

namespace Storefront\Security\RateLimiter;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class VerificationCodeRateLimiter
{
    public function __construct(
        #[Autowire(service: 'limiter.verification_code_resend_ip')]
        private RateLimiterFactory $ipLimiterFactory,
        #[Autowire(service: 'limiter.verification_code_resend_identity')]
        private RateLimiterFactory $identityLimiterFactory,
        #[Autowire('%kernel.secret%')]
        #[\SensitiveParameter]
        private string $secret,
    ) {
    }

    public function consume(Request $request, string $identityId, string $purpose): ?\DateTimeImmutable
    {
        $ip = (string) $request->getClientIp();

        $ipLimit = $this->ipLimiterFactory->create($this->hash($ip))->consume();
        $identityLimit = $this->identityLimiterFactory->create($this->hash($identityId.'-'.$ip.'-'.$purpose))->consume();

        if ($ipLimit->isAccepted() && $identityLimit->isAccepted()) {
            return null;
        }

        return max($ipLimit->getRetryAfter(), $identityLimit->getRetryAfter());
    }

    public function reset(Request $request, string $identityId, string $purpose): void
    {
        $ip = (string) $request->getClientIp();

        $this->identityLimiterFactory->create($this->hash($identityId.'-'.$ip.'-'.$purpose))->reset();
    }

    private function hash(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret);
    }
}
