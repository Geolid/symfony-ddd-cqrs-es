<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Application\Query\ListOrderSummaries;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Sales\OrderSummary\Application\OrderSummaryStatus;
use Sales\OrderSummary\Application\Query\ListOrderSummaries\ListOrderSummaries;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Application\Finder\PaginationMetadata;
use Shared\Application\Query\Result\PaginatedResult;
use Shared\Tests\Support\PaginationTrait;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListOrderSummariesHandlerTest extends AbstractIntegrationTestCase
{
    /** @use PaginationTrait<PaginatedResult<OrderSummaryResult>> */
    use PaginationTrait;

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
        $now = Clock::get()->now();
        $middle = OrderBuilder::new()->withPlacedAt($now->modify('-2 days'))->create();
        $oldest = OrderBuilder::new()->withPlacedAt($now->modify('-3 days'))->create();
        $newest = OrderBuilder::new()->withPlacedAt($now->modify('-1 day'))->create();
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

        $ids = [];
        foreach ($orders as $order) {
            $ids[] = $order->id->toString();
        }

        // When
        $this->traversePages(
            expectedIds: $ids,
            pageSize: 2,
            askPage: $this->askPage(...),
            idsOf: $this->idsOf(...),
            metadataOf: $this->metadataOf(...),
        );
    }

    #[Test]
    public function itPaginatesWhenEmpty(): void
    {
        // When
        $this->traverseEmptyPage(
            askPage: $this->askPage(...),
            idsOf: $this->idsOf(...),
            metadataOf: $this->metadataOf(...),
            itemsPerPage: 20,
        );
    }

    /**
     * @return PaginatedResult<OrderSummaryResult>
     */
    private function askPage(int $page, int $itemsPerPage): PaginatedResult
    {
        return $this->ask(new ListOrderSummaries(page: $page, itemsPerPage: $itemsPerPage));
    }

    /**
     * @param PaginatedResult<OrderSummaryResult> $result
     *
     * @return list<string>
     */
    private function idsOf(PaginatedResult $result): array
    {
        return array_map(static fn (OrderSummaryResult $item): string => $item->orderId, $result->items);
    }

    /**
     * @param PaginatedResult<OrderSummaryResult> $result
     */
    private function metadataOf(PaginatedResult $result): PaginationMetadata
    {
        return $result->pagination;
    }
}
