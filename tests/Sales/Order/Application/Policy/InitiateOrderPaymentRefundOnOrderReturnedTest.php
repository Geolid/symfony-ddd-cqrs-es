<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Policy\InitiateOrderPaymentRefundOnOrderReturned;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Order\Domain\Event\OrderReturned;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class InitiateOrderPaymentRefundOnOrderReturnedTest extends AbstractIntegrationTestCase
{
    private const string RETURNED_AT = '2026-01-12T00:00:00+00:00';

    private InitiateOrderPaymentRefundOnOrderReturned $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(InitiateOrderPaymentRefundOnOrderReturned::class);
    }

    #[Test]
    public function itInitiates(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->withReference('GLBX-9F3K2M1P')->authorized()->captured()->store();

        // When
        ($this->processor)(new OrderReturned($order->id->toString(), self::RETURNED_AT));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-9F3K2M1P');
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED, $result->status);
    }
}
