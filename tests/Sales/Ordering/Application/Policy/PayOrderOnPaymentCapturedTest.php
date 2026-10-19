<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentCaptured\PaymentCapturedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Application\Policy\PayOrderOnPaymentCaptured;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class PayOrderOnPaymentCapturedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPays(): void
    {
        // Given
        $order = OrderFactory::new()->prepared()->create();
        $this->store($order);

        // When
        $this->trigger(PayOrderOnPaymentCaptured::class, new PaymentCapturedIntegrationEvent($order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::PAID, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        $this->trigger(PayOrderOnPaymentCaptured::class, new PaymentCapturedIntegrationEvent($orderId, Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
