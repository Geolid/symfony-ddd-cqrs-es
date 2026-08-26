<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Policy\CancelOrderPaymentOnOrderCancelled;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Support\AbstractIntegrationTestCase;

final class CancelOrderPaymentOnOrderCancelledTest extends AbstractIntegrationTestCase
{
    private const string CANCELLED_AT = '2026-01-02T00:00:00+00:00';

    private CancelOrderPaymentOnOrderCancelled $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(CancelOrderPaymentOnOrderCancelled::class);
    }

    #[Test]
    public function itCancels(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $this->store(OrderPaymentTestFactory::new()->withOrderId($orderId)->withReference('GLBX-9F3K2M1P')->create());

        // When
        ($this->policy)($this->orderCancelled($orderId));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-9F3K2M1P');
        self::assertSame(OrderPaymentStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        ($this->policy)($this->orderCancelled($orderId));

        // Then
        self::expectNotToPerformAssertions();
    }

    private function orderCancelled(string $orderId): OrderCancelled
    {
        return new OrderCancelled($orderId, self::CANCELLED_AT);
    }
}
