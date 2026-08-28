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
use Symfony\Component\Clock\Clock;

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
        $now = Clock::get()->now();
        $placedAt = $now->modify('-11 years');
        $confirmedAt = $placedAt->modify('+1 hour');
        $dispatchedAt = $placedAt->modify('+1 day 2 hours');
        $deliveredAt = $placedAt->modify('+4 days 3 hours');
        $completedAt = $placedAt->modify('+1 month');
        $anonymizedAt = $now;
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
        $other = OrderTestFactory::new()->create();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->create();
        $this->store($other, $order);

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
        $now = Clock::get()->now();
        $cutoff = $now->modify('-30 days');
        $withinCutoff = OrderTestFactory::new()->cancelled($now->modify('-10 days'))->create();
        $notClosed = OrderTestFactory::new()->create();
        $expired = OrderTestFactory::new()->cancelled($now->modify('-60 days'))->create();
        $this->store($withinCutoff, $notClosed, $expired);

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
        $now = Clock::get()->now();
        $cutoff = $now->modify('-14 days');
        $withinCutoff = OrderTestFactory::new()->confirmed()->dispatched()
            ->delivered($now->modify('-5 days'))
            ->create();
        $notDelivered = OrderTestFactory::new()->confirmed()->dispatched()->create();
        $expired = OrderTestFactory::new()->confirmed()->dispatched()
            ->delivered($now->modify('-20 days'))
            ->create();
        $this->store($withinCutoff, $notDelivered, $expired);

        // When
        $results = iterator_to_array($this->finder->deliveredBefore($cutoff), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id->toString(), $results[0]->id);
    }
}
