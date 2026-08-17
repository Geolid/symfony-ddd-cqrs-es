<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CancelOrphanedOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrphanedOrder\CancelOrphanedOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CancelOrphanedOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itCancelsAPlacedOrder(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->store();

        // When
        $this->dispatch(new CancelOrphanedOrder($order->id()->toString(), $customerId));

        // Then
        $result = $this->finder->ofId($order->id()->toString());
        self::assertSame(OrderStatus::CANCELLED, $result->status);
        self::assertNotNull($result->cancelledAt);
    }

    #[Test]
    public function itIgnoresAnAlreadyCancelledOrder(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->cancelled()->store();

        // When
        $this->dispatch(new CancelOrphanedOrder($order->id()->toString(), $customerId));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itIgnoresAnOrderThatIsNoLongerCancellable(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->confirmed()->dispatched()->store();

        // When
        $this->dispatch(new CancelOrphanedOrder($order->id()->toString(), $customerId));

        // Then
        $result = $this->finder->ofId($order->id()->toString());
        self::assertSame(OrderStatus::DISPATCHED, $result->status);
    }

    #[Test]
    public function itFailsWhenTheOrderDoesNotExist(): void
    {
        // Given
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new CancelOrphanedOrder($id, $customerId));
    }
}
