<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Application\Query\ListOrderSummaries;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\OrderSummary\Application\Query\ListOrderSummaries\ListOrderSummaries;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class ListOrderSummariesHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsOrderSummaries(): void
    {
        // Given
        $this->store(OrderTestFactory::new()->create());
        $this->store(OrderTestFactory::new()->create());

        // When
        $result = $this->ask(new ListOrderSummaries());

        // Then
        self::assertCount(2, $result->items);
    }

    #[Test]
    public function itListsOrderSummariesByCustomer(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->create();
        $this->store($order);
        $this->store(OrderTestFactory::new()->withCustomerId(Uuid::uuid7()->toString())->create());

        // When
        $result = $this->ask(new ListOrderSummaries(customerId: $customerId));

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($order->id()->toString(), $result->items[0]->orderId);
    }

    #[Test]
    public function itListsOrderSummariesByStatus(): void
    {
        // Given
        $placed = OrderTestFactory::new()->create();
        $this->store($placed);
        $this->store(OrderTestFactory::new()->cancelled()->create());

        // When
        $result = $this->ask(new ListOrderSummaries(status: 'placed'));

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($placed->id()->toString(), $result->items[0]->orderId);
    }

    #[Test]
    public function itPaginatesOrderSummaries(): void
    {
        // Given
        $this->store(OrderTestFactory::new()->create());
        $this->store(OrderTestFactory::new()->create());
        $this->store(OrderTestFactory::new()->create());

        // When
        $result = $this->ask(new ListOrderSummaries(page: 1, itemsPerPage: 2));

        // Then
        self::assertCount(2, $result->items);
        self::assertSame(3, $result->pagination->totalItems);
        self::assertSame(2, $result->pagination->lastPage);
    }
}
