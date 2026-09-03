<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
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
        $builder = OrderBuilder::new()->confirmed()->dispatched()->delivered()->completed()->anonymized();
        $order = $builder->create();
        $this->store($order);

        // When
        $result = $this->finder->ofId($order->id->toString());

        // Then
        self::assertSame($order->id->toString(), $result->id);
        self::assertSame($builder['customerId'], $result->customerId);
        self::assertSame($order->totalAmountInCents, $result->totalAmountInCents);
        self::assertSame(OrderStatus::COMPLETED, $result->status);
        self::assertSame($builder['placedAt']->format('Y-m-d H:i:s'), $result->placedAt->format('Y-m-d H:i:s'));
        self::assertSame($builder['confirmedAt']->format('Y-m-d H:i:s'), $result->confirmedAt?->format('Y-m-d H:i:s'));
        self::assertSame($builder['dispatchedAt']->format('Y-m-d H:i:s'), $result->dispatchedAt?->format('Y-m-d H:i:s'));
        self::assertSame($builder['deliveredAt']->format('Y-m-d H:i:s'), $result->deliveredAt?->format('Y-m-d H:i:s'));
        self::assertSame($builder['completedAt']->format('Y-m-d H:i:s'), $result->completedAt?->format('Y-m-d H:i:s'));
        self::assertNull($result->cancelledAt);
        self::assertSame($builder['completedAt']->format('Y-m-d H:i:s'), $result->closedAt?->format('Y-m-d H:i:s'));
        self::assertSame($builder['anonymizedAt']->format('Y-m-d H:i:s'), $result->anonymizedAt?->format('Y-m-d H:i:s'));
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
        $other = OrderBuilder::new()->create();
        $order = OrderBuilder::new()->withCustomerId($customerId)->create();
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
        $withinCutoff = OrderBuilder::new()->cancelled($now->modify('-10 days'))->create();
        $notClosed = OrderBuilder::new()->create();
        $expired = OrderBuilder::new()->cancelled($now->modify('-60 days'))->create();
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
        $withinCutoff = OrderBuilder::new()->confirmed()->dispatched()
            ->delivered($now->modify('-5 days'))
            ->create();
        $notDelivered = OrderBuilder::new()->confirmed()->dispatched()->create();
        $expired = OrderBuilder::new()->confirmed()->dispatched()
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
