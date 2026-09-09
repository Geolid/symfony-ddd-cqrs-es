<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentFailed\PaymentFailedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Application\Policy\AbortOrderOnPaymentFailed;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class AbortOrderOnPaymentFailedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAborts(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $this->store($order);

        // When
        $this->trigger(AbortOrderOnPaymentFailed::class, new PaymentFailedIntegrationEvent($order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // Given
        $order = OrderBuilder::new()->create();

        // When
        $this->trigger(AbortOrderOnPaymentFailed::class, new PaymentFailedIntegrationEvent($order->id->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
