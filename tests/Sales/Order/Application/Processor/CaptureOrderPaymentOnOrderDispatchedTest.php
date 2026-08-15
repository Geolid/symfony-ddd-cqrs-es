<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Processor\CaptureOrderPaymentOnOrderDispatched;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CaptureOrderPaymentOnOrderDispatchedTest extends AbstractIntegrationTestCase
{
    private CaptureOrderPaymentOnOrderDispatched $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(CaptureOrderPaymentOnOrderDispatched::class);
    }

    #[Test]
    public function itCapturesTheOrderPaymentOnOrderDispatched(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->authorized()->store();

        // When
        ($this->processor)(new OrderDispatched($order->id()->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofOrderOrNull($order->id()->toString());
        self::assertNotNull($result);
        self::assertSame(OrderPaymentStatus::CAPTURED, $result->status);
    }
}
