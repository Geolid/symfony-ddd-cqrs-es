<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class DbalOrderFinderTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itReadsAnOrderAsItWasPlaced(): void
    {
        // Given
        $order = OrderTestFactory::new()
            ->withCustomerId('customer-1')
            ->withTotalAmountInCents(2_500)
            ->create();
        $this->store($order);

        // When
        $results = iterator_to_array($this->finder->withCustomer('customer-1'));

        // Then
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertInstanceOf(OrderResult::class, $result);
        self::assertSame($order->id()->toString(), $result->id);
        self::assertSame('customer-1', $result->customerId);
        self::assertSame(2_500, $result->totalAmountInCents);
        self::assertSame('placed', $result->status);
        self::assertNull($result->cancelledAt);
    }

    #[Test]
    public function itFiltersOrdersByCustomer(): void
    {
        // Given
        $this->store(OrderTestFactory::new()->withCustomerId('customer-1')->create());
        $this->store(OrderTestFactory::new()->withCustomerId('customer-2')->create());

        // When
        $finder = $this->finder->withCustomer('customer-1');

        // Then
        self::assertCount(1, $finder);
        self::assertSame(2, \count($this->finder));
    }

    #[Test]
    public function itPaginatesOrdersNewestFirst(): void
    {
        // Given
        $older = OrderTestFactory::new()->placedAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))->create();
        $newer = OrderTestFactory::new()->placedAt(new \DateTimeImmutable('2026-01-02T00:00:00+00:00'))->create();
        $this->store($older);
        $this->store($newer);

        // When
        $paginator = $this->finder->paginate(1, 1);

        // Then
        self::assertSame(2, $paginator->totalItems());
        self::assertSame(2, $paginator->lastPage());
        self::assertSame([$newer->id()->toString()], array_map(
            static fn (OrderResult $order): string => $order->id,
            iterator_to_array($paginator),
        ));
    }
}
