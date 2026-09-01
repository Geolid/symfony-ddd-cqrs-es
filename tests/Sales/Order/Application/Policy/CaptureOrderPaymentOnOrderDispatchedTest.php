<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Application\Policy\CaptureOrderPaymentOnOrderDispatched;
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\AbstractIntegrationTestCase;

final class CaptureOrderPaymentOnOrderDispatchedTest extends AbstractIntegrationTestCase
{
    private CaptureOrderPaymentOnOrderDispatched $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(CaptureOrderPaymentOnOrderDispatched::class);
    }

    #[Test]
    public function itCaptures(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $payment = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->withReference('GLBX-9F3K2M1P')->authorized()->create();
        $this->store($order, $payment);

        // When
        ($this->policy)(new OrderDispatched($order->id->toString(), new \DateTimeImmutable('2026-01-02T00:00:00+00:00')));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-9F3K2M1P');
        self::assertSame(OrderPaymentStatus::CAPTURED, $result->status);
    }
}
