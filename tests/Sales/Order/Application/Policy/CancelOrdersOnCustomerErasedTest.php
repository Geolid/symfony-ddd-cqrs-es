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
use Support\AbstractIntegrationTestCase;

final class CancelOrdersOnCustomerErasedTest extends AbstractIntegrationTestCase
{
    private CancelOrdersOnCustomerErased $policy;
    private \DateTimeImmutable $erasedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(CancelOrdersOnCustomerErased::class);
        $this->erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
    }

    #[Test]
    public function itCancelsPlaced(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withCustomerId($customerId)->create();
        $this->store($other, $order);

        // When
        ($this->policy)(new CustomerErasedIntegrationEvent($customerId, $this->erasedAt));

        // Then
        $finder = $this->service(OrderFinderInterface::class);
        $results = iterator_to_array($finder->byCustomer($customerId), false);
        self::assertSame($order->id->toString(), $results[0]->id);
        self::assertSame(OrderStatus::CANCELLED, $results[0]->status);

        $otherResult = $finder->ofId($other->id->toString());
        self::assertSame(OrderStatus::PLACED, $otherResult->status);
    }
}
