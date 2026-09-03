<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Application\Policy\InitiateOrderPaymentRefundOnOrderReturned;
use Sales\Order\Domain\Event\OrderReturned;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class InitiateOrderPaymentRefundOnOrderReturnedTest extends AbstractIntegrationTestCase
{
    private InitiateOrderPaymentRefundOnOrderReturned $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(InitiateOrderPaymentRefundOnOrderReturned::class);
    }

    #[Test]
    public function itInitiates(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $orderPayment = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->withReference('GLBX-9F3K2M1P')->authorized()->captured()->create();
        $this->store($order, $orderPayment);

        // When
        ($this->policy)(new OrderReturned($order->id->toString(), new \DateTimeImmutable('2026-01-12T00:00:00+00:00')));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-9F3K2M1P');
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED, $result->status);
    }
}
