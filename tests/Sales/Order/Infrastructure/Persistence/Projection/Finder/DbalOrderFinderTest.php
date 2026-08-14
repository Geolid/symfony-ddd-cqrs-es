<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Enum\OrderStatus;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Application\Exception\ResultNotFoundException;
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
    public function itGetsAnOrder(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()
            ->withCustomerId($customerId)
            ->withTotalAmountInCents(2_500)
            ->store();

        // When
        $result = $this->finder->ofId($order->id()->toString());

        // Then
        self::assertSame($order->id()->toString(), $result->id);
        self::assertSame($customerId, $result->customerId);
        self::assertSame(2_500, $result->totalAmountInCents);
        self::assertSame(OrderStatus::PLACED, $result->status);
        self::assertNull($result->cancelledAt);
    }

    #[Test]
    public function itThrowsOnAnUnknownOrder(): void
    {
        // Then
        $this->expectException(ResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFiltersByCustomer(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->store();
        OrderTestFactory::new()->store();

        // When
        $results = iterator_to_array($this->finder->byCustomer($customerId), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($order->id()->toString(), $results[0]->id);
    }

    #[Test]
    public function itFiltersOrdersPlacedBeforeACutoff(): void
    {
        // Given
        $cutoff = '2026-01-01T00:00:00+00:00';
        $expired = OrderTestFactory::new()->withPlacedAt(new \DateTimeImmutable('2015-01-01T00:00:00+00:00'))->store();
        OrderTestFactory::new()->withPlacedAt(new \DateTimeImmutable('2026-06-01T00:00:00+00:00'))->store();

        // When
        $results = iterator_to_array($this->finder->placedBefore($cutoff), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id()->toString(), $results[0]->id);
    }
}
