<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Application\Query\ListOrderSummaries;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\OrderSummary\Application\OrderSummaryStatus;
use Sales\OrderSummary\Application\Query\ListOrderSummaries\ListOrderSummaries;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\AbstractIntegrationTestCase;

final class ListOrderSummariesHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();

        $orders = [
            OrderBuilder::new()->withCustomerId($customerId)->withTotalAmountInCents(4_200)->create(),
            ...OrderBuilder::new()->many(4)->create(),
        ];
        $this->store(...$orders);

        // When
        $result = $this->ask(new ListOrderSummaries());

        // Then
        self::assertCount(5, $result);

        self::assertSame($customerId, $result->items[0]->customerId);
        self::assertSame(4_200, $result->items[0]->totalAmountInCents);

        for ($i = 1; $i < 5; ++$i) {
            self::assertSame($orders[$i]->id->toString(), $result->items[$i]->orderId);
        }
    }

    #[Test]
    public function itListsByCustomer(): void
    {
        // Given
        $others = OrderBuilder::new()->many(2)->create();
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withCustomerId($customerId)->create();
        $this->store($order, ...$others);

        // When
        $result = $this->ask(new ListOrderSummaries(customerId: $customerId));

        // Then
        self::assertCount(1, $result);
        self::assertSame($order->id->toString(), $result->items[0]->orderId);
    }

    #[Test]
    public function itListsByStatus(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $cancelled = OrderBuilder::new()->cancelled()->create();
        $this->store($other, $cancelled);

        // When
        $result = $this->ask(new ListOrderSummaries(status: OrderSummaryStatus::CANCELLED));

        // Then
        self::assertCount(1, $result);
        self::assertSame($cancelled->id->toString(), $result->items[0]->orderId);
    }

    #[Test]
    public function itListsSortedByPlacedAt(): void
    {
        // Given
        $middle = OrderBuilder::new()->withPlacedAt(new \DateTimeImmutable('-2 days +00:00'))->create();
        $oldest = OrderBuilder::new()->withPlacedAt(new \DateTimeImmutable('-3 days +00:00'))->create();
        $newest = OrderBuilder::new()->withPlacedAt(new \DateTimeImmutable('-1 day +00:00'))->create();
        $this->store($middle, $oldest, $newest);

        // When
        $result = $this->ask(new ListOrderSummaries(sortedByPlacedAt: true));

        // Then
        self::assertCount(3, $result);
        self::assertSame($newest->id->toString(), $result->items[0]->orderId);
        self::assertSame($middle->id->toString(), $result->items[1]->orderId);
        self::assertSame($oldest->id->toString(), $result->items[2]->orderId);
    }

    #[Test]
    public function itPaginates(): void
    {
        // Given
        $orders = OrderBuilder::new()->many(5)->create();
        $this->store(...$orders);

        // When
        $firstPage = $this->ask(new ListOrderSummaries(page: 1, itemsPerPage: 2));
        $secondPage = $this->ask(new ListOrderSummaries(page: 2, itemsPerPage: 2));
        $lastPage = $this->ask(new ListOrderSummaries(page: 3, itemsPerPage: 2));
        $outOfBoundsPage = $this->ask(new ListOrderSummaries(page: 4, itemsPerPage: 2));

        // Then
        self::assertCount(2, $firstPage);
        self::assertSame($orders[0]->id->toString(), $firstPage->items[0]->orderId);
        self::assertSame($orders[1]->id->toString(), $firstPage->items[1]->orderId);
        self::assertSame(5, $firstPage->pagination->totalItems);
        self::assertSame(1, $firstPage->pagination->currentPage);
        self::assertSame(2, $firstPage->pagination->itemsPerPage);
        self::assertSame(3, $firstPage->pagination->lastPage);

        self::assertCount(2, $secondPage);
        self::assertSame($orders[2]->id->toString(), $secondPage->items[0]->orderId);
        self::assertSame($orders[3]->id->toString(), $secondPage->items[1]->orderId);
        self::assertSame(2, $secondPage->pagination->currentPage);

        self::assertCount(1, $lastPage);
        self::assertSame($orders[4]->id->toString(), $lastPage->items[0]->orderId);
        self::assertSame(3, $lastPage->pagination->currentPage);

        self::assertCount(0, $outOfBoundsPage);
        self::assertSame(4, $outOfBoundsPage->pagination->currentPage);
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $result = $this->ask(new ListOrderSummaries());

        // Then
        self::assertCount(0, $result->items);
        self::assertSame(0, $result->pagination->totalItems);
        self::assertSame(1, $result->pagination->currentPage);
        self::assertSame(20, $result->pagination->itemsPerPage);
        self::assertSame(1, $result->pagination->lastPage);
    }
}
