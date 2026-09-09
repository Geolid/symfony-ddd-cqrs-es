<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentFailed\PaymentFailedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Application\Policy\FailOrderOnPaymentFailed;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class FailOrderOnPaymentFailedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itFails(): void
    {
        // Given
        $order = OrderBuilder::new()->prepared()->create();
        $this->store($order);

        // When
        $this->trigger(FailOrderOnPaymentFailed::class, new PaymentFailedIntegrationEvent($order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::FAILED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // When
        $this->trigger(FailOrderOnPaymentFailed::class, new PaymentFailedIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
