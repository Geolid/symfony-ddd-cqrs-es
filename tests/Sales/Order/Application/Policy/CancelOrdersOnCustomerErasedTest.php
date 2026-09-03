<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Application\Policy\CancelOrdersOnCustomerErased;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CancelOrdersOnCustomerErasedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancelsPlaced(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withCustomerId($customerId)->create();
        $this->store($other, $order);

        // When
        $this->trigger(CancelOrdersOnCustomerErased::class, new CustomerErasedIntegrationEvent($customerId, Clock::get()->now()));

        // Then
        $finder = $this->service(OrderFinderInterface::class);
        $results = iterator_to_array($finder->byCustomer($customerId), false);
        self::assertSame($order->id->toString(), $results[0]->id);
        self::assertSame(OrderStatus::CANCELLED, $results[0]->status);

        $otherResult = $finder->ofId($other->id->toString());
        self::assertSame(OrderStatus::PLACED, $otherResult->status);
    }
}
