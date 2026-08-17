<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Payment;

use Sales\Order\Application\Exception\OrderPaymentRequestInProgressException;
use Sales\Order\Application\Payment\OrderPaymentRequester;
use Sales\Order\Application\Payment\OrderPaymentRequesterInterface;
use Shared\Infrastructure\Locking\LockingTrait;
use Shared\Infrastructure\Locking\LockNotAcquiredException;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Lock\LockFactory;

#[AsDecorator(decorates: OrderPaymentRequester::class)]
final readonly class LockingOrderPaymentRequester implements OrderPaymentRequesterInterface
{
    use LockingTrait;

    // Must exceed the globex.client HTTP timeout, or a slow-but-successful
    // gateway call could outlive the lock and let a concurrent retry race it.
    private const float LOCK_TTL_SECONDS = 30.0;

    public function __construct(
        #[AutowireDecorated]
        private OrderPaymentRequesterInterface $inner,
        private LockFactory $lockFactory,
    ) {
    }

    /**
     * @throws OrderPaymentRequestInProgressException
     */
    public function requestFor(string $orderId, string $returnUrl): string
    {
        try {
            return $this->withLock(
                \sprintf('sales.order.payment_request.%s', $orderId),
                self::LOCK_TTL_SECONDS,
                fn (): string => $this->inner->requestFor($orderId, $returnUrl),
            );
        } catch (LockNotAcquiredException $e) {
            throw OrderPaymentRequestInProgressException::forOrderId($orderId, $e);
        }
    }

    private function locks(): LockFactory
    {
        return $this->lockFactory;
    }
}
