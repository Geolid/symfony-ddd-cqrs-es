<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Policy\CancelOrdersOnCustomerErased;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CancelOrdersOnCustomerErasedTest extends AbstractIntegrationTestCase
{
    private const string ERASED_AT = '2026-01-02T00:00:00+00:00';

    private CancelOrdersOnCustomerErased $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(CancelOrdersOnCustomerErased::class);
    }

    #[Test]
    public function itCancelsPlaced(): void
    {
        // Given
        $other = OrderTestFactory::new()->create();
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->create();
        $this->store($other, $order);

        // When
        ($this->policy)(new CustomerErasedIntegrationEvent($customerId, self::ERASED_AT));

        // Then
        $finder = $this->service(OrderFinderInterface::class);
        $results = iterator_to_array($finder->byCustomer($customerId), false);
        self::assertSame($order->id->toString(), $results[0]->id);
        self::assertSame(OrderStatus::CANCELLED, $results[0]->status);

        $otherResult = $finder->ofId($other->id->toString());
        self::assertSame(OrderStatus::PLACED, $otherResult->status);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $otherCustomerId = Uuid::uuid7()->toString();
        $this->store(OrderTestFactory::new()->withCustomerId($otherCustomerId)->create());

        // When
        ($this->policy)(new CustomerErasedIntegrationEvent($customerId, self::ERASED_AT));

        // Then
        $results = iterator_to_array($this->service(OrderFinderInterface::class)->byCustomer($otherCustomerId), false);
        self::assertSame(OrderStatus::PLACED, $results[0]->status);
    }
}
