<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $builder = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered();
        $order = $builder->create();
        $this->store($order);

        // When
        $result = $this->finder->ofId($order->id->toString());

        // Then
        self::assertSame($order->id->toString(), $result->id);
        self::assertSame($builder['buyerId'], $result->buyerId);
        self::assertSame($order->totalAmountInCents, $result->totalAmountInCents);
        self::assertSame(OrderStatus::DELIVERED, $result->status);
        self::assertSame($builder['placedAt']->format('Y-m-d H:i:s'), $result->placedAt->format('Y-m-d H:i:s'));
        self::assertSame($builder['confirmedAt']->format('Y-m-d H:i:s'), $result->confirmedAt?->format('Y-m-d H:i:s'));
        self::assertSame($builder['preparedAt']->format('Y-m-d H:i:s'), $result->preparedAt?->format('Y-m-d H:i:s'));
        self::assertSame($builder['dispatchedAt']->format('Y-m-d H:i:s'), $result->dispatchedAt?->format('Y-m-d H:i:s'));
        self::assertSame($builder['deliveredAt']->format('Y-m-d H:i:s'), $result->deliveredAt?->format('Y-m-d H:i:s'));
        self::assertNull($result->returnRequestedAt);
        self::assertNull($result->returnedAt);
        self::assertNull($result->disputedAt);
        self::assertNull($result->cancelledAt);
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
    public function itFiltersByBuyer(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $other = OrderBuilder::new()->create();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->create();
        $this->store($other, $order);

        // When
        $results = iterator_to_array($this->finder->byBuyer($buyerId), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($order->id->toString(), $results[0]->id);
    }
}
