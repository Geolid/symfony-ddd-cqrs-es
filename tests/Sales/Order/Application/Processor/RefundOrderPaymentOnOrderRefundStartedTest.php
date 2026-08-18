<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Processor\RefundOrderPaymentOnOrderRefundStarted;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Order\Domain\Event\OrderRefundStarted;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class RefundOrderPaymentOnOrderRefundStartedTest extends AbstractIntegrationTestCase
{
    private const string STARTED_AT = '2026-01-11T00:00:00+00:00';

    private RefundOrderPaymentOnOrderRefundStarted $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(RefundOrderPaymentOnOrderRefundStarted::class);
    }

    #[Test]
    public function itRefundsTheOrderPaymentOnOrderRefundStarted(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->withReference('GLBX-9F3K2M1P')->authorized()->captured()->store();

        // When
        ($this->processor)(new OrderRefundStarted($order->id()->toString(), self::STARTED_AT));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-9F3K2M1P');
        self::assertSame(OrderPaymentStatus::REFUNDING, $result->status);
    }
}
