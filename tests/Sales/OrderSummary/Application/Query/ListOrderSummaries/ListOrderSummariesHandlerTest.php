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
        // placedAt is set explicitly — the finder orders by placed_at DESC, so the later date sorts first.
        $customerId1 = Uuid::uuid7()->toString();
        $customerId2 = Uuid::uuid7()->toString();
        $older = OrderTestFactory::new()->withCustomerId($customerId1)->withTotalAmountInCents(1_500)->placedAt(new \DateTimeImmutable('-2 days +00:00'))->create();
        $this->store($older);
        $newer = OrderTestFactory::new()->withCustomerId($customerId2)->withTotalAmountInCents(2_500)->placedAt(new \DateTimeImmutable('-1 day +00:00'))->create();
        $this->store($newer);

        // When
        $result = $this->ask(new ListOrderSummaries());

        // Then
        self::assertCount(2, $result->items);
        self::assertSame($newer->id()->toString(), $result->items[0]->orderId);
        self::assertSame($customerId2, $result->items[0]->customerId);
        self::assertSame(2_500, $result->items[0]->totalAmountInCents);
        self::assertSame('placed', $result->items[0]->status->value);
        self::assertSame($older->id()->toString(), $result->items[1]->orderId);
        self::assertSame($customerId1, $result->items[1]->customerId);
        self::assertSame(1_500, $result->items[1]->totalAmountInCents);
    }

    #[Test]
    public function itListsOrderSummariesByCustomer(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->withTotalAmountInCents(1_500)->create();
        $this->store($order);
        $this->store(OrderTestFactory::new()->withCustomerId(Uuid::uuid7()->toString())->create());

        // When
        $result = $this->ask(new ListOrderSummaries(customerId: $customerId));

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($order->id()->toString(), $result->items[0]->orderId);
        self::assertSame($customerId, $result->items[0]->customerId);
        self::assertSame(1_500, $result->items[0]->totalAmountInCents);
    }

    #[Test]
    public function itListsOrderSummariesByStatus(): void
    {
        // Given
        $placed = OrderTestFactory::new()->create();
        $this->store($placed);
        $cancelled = OrderTestFactory::new()->cancelled()->create();
        $this->store($cancelled);

        // When
        $result = $this->ask(new ListOrderSummaries(status: 'placed'));

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($placed->id()->toString(), $result->items[0]->orderId);
        self::assertSame('placed', $result->items[0]->status->value);
    }

    #[Test]
    public function itPaginatesOrderSummaries(): void
    {
        // Given
        // placedAt is set explicitly and strictly increasing — the finder orders by placed_at DESC, 2 per
        // page over 5 orders makes 3 pages, so the most recently placed order starts page 1.
        $orders = [];
        foreach (range(1, 5) as $daysAgo) {
            $order = OrderTestFactory::new()->placedAt(new \DateTimeImmutable("-{$daysAgo} days +00:00"))->create();
            $this->store($order);
            $orders[$daysAgo] = $order;
        }

        // When
        $firstPage = $this->ask(new ListOrderSummaries(page: 1, itemsPerPage: 2));
        $secondPage = $this->ask(new ListOrderSummaries(page: 2, itemsPerPage: 2));
        $lastPage = $this->ask(new ListOrderSummaries(page: 3, itemsPerPage: 2));

        // Then
        self::assertSame(
            [$orders[1]->id()->toString(), $orders[2]->id()->toString()],
            array_map(static fn ($item) => $item->orderId, $firstPage->items),
        );
        self::assertSame(5, $firstPage->pagination->totalItems);
        self::assertSame(1, $firstPage->pagination->currentPage);
        self::assertSame(2, $firstPage->pagination->itemsPerPage);
        self::assertSame(3, $firstPage->pagination->lastPage);

        self::assertSame(
            [$orders[3]->id()->toString(), $orders[4]->id()->toString()],
            array_map(static fn ($item) => $item->orderId, $secondPage->items),
        );
        self::assertSame(2, $secondPage->pagination->currentPage);

        self::assertSame([$orders[5]->id()->toString()], array_map(static fn ($item) => $item->orderId, $lastPage->items));
        self::assertSame(3, $lastPage->pagination->currentPage);
    }
}
