<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\Requesting;

use Finance\Payment\Application\PSP\PaymentLine;
use Finance\Payment\Application\Requesting\Exception\PaymentRequestInProgressException;
use Finance\Payment\Application\Requesting\PaymentRequester;
use Finance\Payment\Application\Requesting\PaymentRequesterInterface;
use Shared\Infrastructure\Locking\Exception\LockNotAcquiredException;
use Shared\Infrastructure\Locking\LockingTrait;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Lock\LockFactory;

#[AsDecorator(decorates: PaymentRequester::class)]
final readonly class LockingPaymentRequester implements PaymentRequesterInterface
{
    use LockingTrait;

    // Must exceed the globex.client HTTP timeout, or a slow-but-successful
    // gateway call could outlive the lock and let a concurrent retry race it.
    private const float LOCK_TTL_SECONDS = 30.0;

    public function __construct(
        #[AutowireDecorated]
        private PaymentRequesterInterface $inner,
        private LockFactory $lockFactory,
    ) {
    }

    /**
     * @param list<PaymentLine> $lines
     *
     * @throws PaymentRequestInProgressException
     */
    public function requestFor(string $checkoutSessionId, string $currency, array $lines, string $successUrl, string $cancelUrl, \DateTimeImmutable $expiresAt): string
    {
        try {
            return $this->withLock(
                \sprintf('finance.payment.payment_request.%s', $checkoutSessionId),
                self::LOCK_TTL_SECONDS,
                fn (): string => $this->inner->requestFor($checkoutSessionId, $currency, $lines, $successUrl, $cancelUrl, $expiresAt),
            );
        } catch (LockNotAcquiredException $e) {
            throw PaymentRequestInProgressException::forCheckoutSession($checkoutSessionId, $e);
        }
    }

    private function locks(): LockFactory
    {
        return $this->lockFactory;
    }
}
