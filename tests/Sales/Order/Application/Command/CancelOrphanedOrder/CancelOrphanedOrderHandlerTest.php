<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CancelOrphanedOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrphanedOrder\CancelOrphanedOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelOrphanedOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itCancels(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->create();
        $this->store($order);

        // When
        $this->dispatch(new CancelOrphanedOrder($order->id->toString(), $buyerId));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::CANCELLED, $result->status);
        self::assertNotNull($result->cancelledAt);
    }

    #[Test]
    public function itIgnoresWhenAlreadyCancelled(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->cancelled()->create();
        $this->store($order);

        // When
        $this->dispatch(new CancelOrphanedOrder($order->id->toString(), $buyerId));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itIgnoresWhenNoLongerCancellable(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->confirmed()->dispatched()->create();
        $this->store($order);

        // When
        $this->dispatch(new CancelOrphanedOrder($order->id->toString(), $buyerId));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::DISPATCHED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = OrderId::generate()->toString();
        $buyerId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new CancelOrphanedOrder($id, $buyerId));
    }
}
