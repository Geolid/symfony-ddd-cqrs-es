<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Enum\OrderPaymentStatus;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Processor\CancelOrderPaymentOnOrderCancelled;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Support\AbstractIntegrationTestCase;

final class CancelOrderPaymentOnOrderCancelledTest extends AbstractIntegrationTestCase
{
    private const string CANCELLED_AT = '2026-01-02T00:00:00+00:00';

    private CancelOrderPaymentOnOrderCancelled $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(CancelOrderPaymentOnOrderCancelled::class);
    }

    #[Test]
    public function itCancelsTheOrderPaymentOnOrderCancelled(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        OrderPaymentTestFactory::new()->withOrderId($orderId)->store();

        // When
        ($this->processor)($this->orderCancelled($orderId));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofOrderOrNull($orderId);
        self::assertNotNull($result);
        self::assertSame(OrderPaymentStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function itDoesNothingWhenNoPaymentExistsForTheOrder(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        ($this->processor)($this->orderCancelled($orderId));

        // Then
        self::expectNotToPerformAssertions();
    }

    private function orderCancelled(string $orderId): OrderCancelled
    {
        return new OrderCancelled($orderId, self::CANCELLED_AT);
    }
}
