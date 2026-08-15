<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Enum\OrderStatus;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
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
    public function itGetsAnOrder(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T08:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-01T09:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-02T10:00:00+00:00');
        $completedAt = new \DateTimeImmutable('2026-01-05T11:00:00+00:00');
        $order = OrderTestFactory::new()
            ->withCustomerId($customerId)
            ->withTotalAmountInCents(2_500)
            ->withPlacedAt($placedAt)
            ->confirmed($confirmedAt)
            ->dispatched($dispatchedAt)
            ->completed($completedAt)
            ->store();

        // When
        $result = $this->finder->ofId($order->id()->toString());

        // Then
        self::assertSame($order->id()->toString(), $result->id);
        self::assertSame($customerId, $result->customerId);
        self::assertSame(2_500, $result->totalAmountInCents);
        self::assertSame(OrderStatus::COMPLETED, $result->status);
        self::assertSame($placedAt->format('Y-m-d H:i:s'), $result->placedAt->format('Y-m-d H:i:s'));
        self::assertSame($confirmedAt->format('Y-m-d H:i:s'), $result->confirmedAt?->format('Y-m-d H:i:s'));
        self::assertSame($dispatchedAt->format('Y-m-d H:i:s'), $result->dispatchedAt?->format('Y-m-d H:i:s'));
        self::assertSame($completedAt->format('Y-m-d H:i:s'), $result->completedAt?->format('Y-m-d H:i:s'));
        self::assertNull($result->cancelledAt);
    }

    #[Test]
    public function itThrowsOnAnUnknownOrder(): void
    {
        // Then
        $this->expectException(OrderResultNotFoundException::class);

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
