<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Application\Query\ListOrderSummaries;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\OrderSummary\Application\Enum\OrderSummaryStatus;
use Sales\OrderSummary\Application\Query\ListOrderSummaries\ListOrderSummaries;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class ListOrderSummariesHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();

        $orders = [
            OrderTestFactory::new()->withCustomerId($customerId)->withTotalAmountInCents(4_200)->create(),
            ...OrderTestFactory::createMany(4),
        ];
        $this->store(...$orders);

        // When
        $result = $this->ask(new ListOrderSummaries());

        // Then
        self::assertCount(5, $result);

        self::assertSame($customerId, $result->items[0]->customerId);
        self::assertSame(4_200, $result->items[0]->totalAmountInCents);

        for ($i = 1; $i < 5; ++$i) {
            self::assertSame($orders[$i]->id()->toString(), $result->items[$i]->orderId);
        }
    }

    #[Test]
    public function itListsByCustomer(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->create();
        $this->store($order, ...OrderTestFactory::createMany(2));

        // When
        $result = $this->ask(new ListOrderSummaries(customerId: $customerId));

        // Then
        self::assertCount(1, $result);
        self::assertSame($order->id()->toString(), $result->items[0]->orderId);
    }

    #[Test]
    public function itListsByStatus(): void
    {
        // Given
        $cancelled = OrderTestFactory::new()->cancelled()->create();
        $this->store($cancelled, OrderTestFactory::createOne());

        // When
        $result = $this->ask(new ListOrderSummaries(status: OrderSummaryStatus::CANCELLED->value));

        // Then
        self::assertCount(1, $result);
        self::assertSame($cancelled->id()->toString(), $result->items[0]->orderId);
    }

    #[Test]
    public function itSortsByPlacedAt(): void
    {
        // Given
        $middle = OrderTestFactory::new()->placedAt(new \DateTimeImmutable('-2 days +00:00'))->create();
        $oldest = OrderTestFactory::new()->placedAt(new \DateTimeImmutable('-3 days +00:00'))->create();
        $newest = OrderTestFactory::new()->placedAt(new \DateTimeImmutable('-1 day +00:00'))->create();
        $this->store($middle, $oldest, $newest);

        // When
        $result = $this->ask(new ListOrderSummaries(sortedByPlacedAt: true));

        // Then
        self::assertCount(3, $result);
        self::assertSame($newest->id()->toString(), $result->items[0]->orderId);
        self::assertSame($middle->id()->toString(), $result->items[1]->orderId);
        self::assertSame($oldest->id()->toString(), $result->items[2]->orderId);
    }

    #[Test]
    public function itPaginates(): void
    {
        // Given
        $orders = OrderTestFactory::createMany(5);
        $this->store(...$orders);

        // When
        $result = $this->ask(new ListOrderSummaries(page: 2, itemsPerPage: 2));

        // Then
        self::assertCount(2, $result);
        self::assertSame($orders[2]->id()->toString(), $result->items[0]->orderId);
        self::assertSame($orders[3]->id()->toString(), $result->items[1]->orderId);

        self::assertSame(5, $result->pagination->totalItems);
        self::assertSame(2, $result->pagination->currentPage);
        self::assertSame(2, $result->pagination->itemsPerPage);
        self::assertSame(3, $result->pagination->lastPage);
    }
}
