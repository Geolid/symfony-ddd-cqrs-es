<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\DispatchOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\DispatchOrder\DispatchOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class DispatchOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itDispatchesWhenConfirmed(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->create();
        $this->store($order);

        // When
        $this->dispatch(new DispatchOrder($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::DISPATCHED, $result->status);
        self::assertNotNull($result->dispatchedAt);
    }

    #[Test]
    public function itIgnoresWhenNotConfirmed(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);

        // When
        $this->dispatch(new DispatchOrder($order->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = OrderId::generate()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new DispatchOrder($id));
    }
}
