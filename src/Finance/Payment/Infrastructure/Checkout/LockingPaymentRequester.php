<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\Checkout;

use Finance\Payment\Application\Checkout\Exception\PaymentRequestInProgressException;
use Finance\Payment\Application\Checkout\PaymentRequester;
use Finance\Payment\Application\Checkout\PaymentRequesterInterface;
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
     * @throws PaymentRequestInProgressException
     */
    public function requestFor(string $orderId, string $returnUrl): string
    {
        try {
            return $this->withLock(
                \sprintf('finance.payment.payment_request.%s', $orderId),
                self::LOCK_TTL_SECONDS,
                fn (): string => $this->inner->requestFor($orderId, $returnUrl),
            );
        } catch (LockNotAcquiredException $e) {
            throw PaymentRequestInProgressException::forOrder($orderId, $e);
        }
    }

    private function locks(): LockFactory
    {
        return $this->lockFactory;
    }
}
