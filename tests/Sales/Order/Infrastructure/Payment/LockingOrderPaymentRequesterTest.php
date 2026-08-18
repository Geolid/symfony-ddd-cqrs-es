<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Payment;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Application\Exception\OrderPaymentRequestInProgressException;
use Sales\Order\Application\Payment\OrderPaymentRequesterInterface;
use Sales\Order\Infrastructure\Payment\LockingOrderPaymentRequester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\InMemoryStore;

final class LockingOrderPaymentRequesterTest extends TestCase
{
    private LockFactory $lockFactory;

    private SpyOrderPaymentRequester $inner;

    private LockingOrderPaymentRequester $requester;

    protected function setUp(): void
    {
        $this->lockFactory = new LockFactory(new InMemoryStore());
        $this->inner = new SpyOrderPaymentRequester();
        $this->requester = new LockingOrderPaymentRequester($this->inner, $this->lockFactory);
    }

    #[Test]
    public function itDelegatesToInnerRequester(): void
    {
        // When
        $checkoutUrl = $this->requester->requestFor('order-id', 'https://web.test/sales/orders');

        // Then
        self::assertSame(SpyOrderPaymentRequester::CHECKOUT_URL, $checkoutUrl);
        self::assertSame('order-id', $this->inner->orderId);
        self::assertSame('https://web.test/sales/orders', $this->inner->returnUrl);
    }

    #[Test]
    public function itReleasesLockAfterSuccessfulRequest(): void
    {
        // Given
        // A plain second call would pass even without an explicit release: the Lock object's
        // own destructor releases it as soon as it goes out of scope, regardless. Retaining it
        // here rules that out — only an explicit release() keeps `isAcquired()` truthful.
        $lockFactory = new SpyLockFactory(new InMemoryStore());
        $requester = new LockingOrderPaymentRequester($this->inner, $lockFactory);

        // When
        $requester->requestFor('order-id', 'https://web.test/sales/orders');

        // Then
        self::assertCount(1, $lockFactory->createdLocks);
        self::assertFalse($lockFactory->createdLocks[0]->isAcquired());
    }

    #[Test]
    public function itFailsWhenRequestInProgress(): void
    {
        // Given
        $lock = $this->lockFactory->createLock('sales.order.payment_request.order-id');
        $lock->acquire();

        // Then
        $this->expectException(OrderPaymentRequestInProgressException::class);

        // When
        try {
            $this->requester->requestFor('order-id', 'https://web.test/sales/orders');
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function itAllowsConcurrentRequestForAnotherOrder(): void
    {
        // Given
        $lock = $this->lockFactory->createLock('sales.order.payment_request.another-order-id');
        $lock->acquire();

        // When
        try {
            $checkoutUrl = $this->requester->requestFor('order-id', 'https://web.test/sales/orders');
        } finally {
            $lock->release();
        }

        // Then
        self::assertSame(SpyOrderPaymentRequester::CHECKOUT_URL, $checkoutUrl);
    }
}

final class SpyOrderPaymentRequester implements OrderPaymentRequesterInterface
{
    public const string CHECKOUT_URL = 'https://fake-checkout.test/?ref=GLBX-9F3K2M1P';

    public ?string $orderId = null;

    public ?string $returnUrl = null;

    public int $callCount = 0;

    public function requestFor(string $orderId, string $returnUrl): string
    {
        ++$this->callCount;
        $this->orderId = $orderId;
        $this->returnUrl = $returnUrl;

        return self::CHECKOUT_URL;
    }
}

final class SpyLockFactory extends LockFactory
{
    /** @var list<SharedLockInterface> */
    public array $createdLocks = [];

    public function createLock(string $resource, ?float $ttl = 300.0, bool $autoRelease = true): SharedLockInterface
    {
        $lock = parent::createLock($resource, $ttl, $autoRelease);
        $this->createdLocks[] = $lock;

        return $lock;
    }
}
