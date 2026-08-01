<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\ListOrders;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Query\ListOrders\ListOrders;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class ListOrdersHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsOrders(): void
    {
        // Given
        $order = OrderTestFactory::new()->withCustomerId('customer-1')->create();
        $this->store($order);

        // When
        $result = $this->ask(new ListOrders());

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($order->id()->toString(), $result->items[0]->id);
        self::assertSame(1, $result->pagination->totalItems);
        self::assertSame(1, $result->pagination->currentPage);
        self::assertSame(20, $result->pagination->itemsPerPage);
        self::assertSame(1, $result->pagination->lastPage);
    }

    #[Test]
    public function itPaginatesOrders(): void
    {
        // Given
        $this->store(OrderTestFactory::new()->placedAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))->create());
        $newer = OrderTestFactory::new()->placedAt(new \DateTimeImmutable('2026-01-02T00:00:00+00:00'))->create();
        $this->store($newer);

        // When
        $result = $this->ask(new ListOrders(page: 1, itemsPerPage: 1));

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($newer->id()->toString(), $result->items[0]->id);
        self::assertSame(2, $result->pagination->totalItems);
        self::assertSame(2, $result->pagination->lastPage);
    }
}
