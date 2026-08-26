<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
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
    public function itGets(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T08:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-01T09:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-02T10:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-05T11:00:00+00:00');
        $completedAt = new \DateTimeImmutable('2026-02-01T00:00:00+00:00');
        $anonymizedAt = new \DateTimeImmutable('2036-02-02T12:00:00+00:00');
        $order = OrderTestFactory::new()
            ->withCustomerId($customerId)
            ->withTotalAmountInCents(2_500)
            ->withPlacedAt($placedAt)
            ->confirmed($confirmedAt)
            ->dispatched($dispatchedAt)
            ->delivered($deliveredAt)
            ->completed($completedAt)
            ->anonymized($anonymizedAt)
            ->create();
        $this->store($order);

        // When
        $result = $this->finder->ofId($order->id->toString());

        // Then
        self::assertSame($order->id->toString(), $result->id);
        self::assertSame($customerId, $result->customerId);
        self::assertSame(2_500, $result->totalAmountInCents);
        self::assertSame(OrderStatus::COMPLETED, $result->status);
        self::assertSame($placedAt->format('Y-m-d H:i:s'), $result->placedAt->format('Y-m-d H:i:s'));
        self::assertSame($confirmedAt->format('Y-m-d H:i:s'), $result->confirmedAt?->format('Y-m-d H:i:s'));
        self::assertSame($dispatchedAt->format('Y-m-d H:i:s'), $result->dispatchedAt?->format('Y-m-d H:i:s'));
        self::assertSame($deliveredAt->format('Y-m-d H:i:s'), $result->deliveredAt?->format('Y-m-d H:i:s'));
        self::assertSame($completedAt->format('Y-m-d H:i:s'), $result->completedAt?->format('Y-m-d H:i:s'));
        self::assertNull($result->cancelledAt);
        self::assertSame($completedAt->format('Y-m-d H:i:s'), $result->closedAt?->format('Y-m-d H:i:s'));
        self::assertSame($anonymizedAt->format('Y-m-d H:i:s'), $result->anonymizedAt?->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function itThrowsOnUnknown(): void
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
        $order = OrderTestFactory::new()->withCustomerId($customerId)->create();
        $this->store($order, OrderTestFactory::new()->create());

        // When
        $results = iterator_to_array($this->finder->byCustomer($customerId), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($order->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itFiltersClosedBefore(): void
    {
        // Given
        $cutoff = '2026-01-01T00:00:00+00:00';
        $expired = OrderTestFactory::new()->cancelled(new \DateTimeImmutable('2015-01-01T00:00:00+00:00'))->create();
        $this->store(
            $expired,
            OrderTestFactory::new()->cancelled(new \DateTimeImmutable('2026-06-01T00:00:00+00:00'))->create(),
            OrderTestFactory::new()->create(),
        );

        // When
        $results = iterator_to_array($this->finder->closedBefore($cutoff), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itFiltersDeliveredBefore(): void
    {
        // Given
        $cutoff = '2026-01-15T00:00:00+00:00';
        $expired = OrderTestFactory::new()->confirmed()->dispatched()
            ->delivered(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->create();
        $this->store(
            $expired,
            OrderTestFactory::new()->confirmed()->dispatched()
                ->delivered(new \DateTimeImmutable('2026-01-20T00:00:00+00:00'))
                ->create(),
            OrderTestFactory::new()->confirmed()->dispatched()->create(),
        );

        // When
        $results = iterator_to_array($this->finder->deliveredBefore($cutoff), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id->toString(), $results[0]->id);
    }
}
