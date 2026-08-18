<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Processor\ReturnOrderOnOrderPaymentRefunded;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Domain\Event\OrderPaymentRefunded;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class ReturnOrderOnOrderPaymentRefundedTest extends AbstractIntegrationTestCase
{
    private ReturnOrderOnOrderPaymentRefunded $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(ReturnOrderOnOrderPaymentRefunded::class);
    }

    #[Test]
    public function itReturnsTheOrderOnOrderPaymentRefunded(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->completed()->returnRequested()->refundStarted()->store();

        // When
        ($this->processor)(new OrderPaymentRefunded(Uuid::uuid7()->toString(), $order->id()->toString(), '2026-01-12T00:00:00+00:00'));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id()->toString());
        self::assertSame(OrderStatus::RETURNED, $result->status);
    }
}
