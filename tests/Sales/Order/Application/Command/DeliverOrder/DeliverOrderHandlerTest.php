<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\DeliverOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\DeliverOrder\DeliverOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class DeliverOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itDeliversWhenDispatched(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->create();
        $this->store($order);

        // When
        $this->dispatch(new DeliverOrder($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::DELIVERED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotDispatched(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->create();
        $this->store($order);

        // When
        $this->dispatch(new DeliverOrder($order->id->toString()));

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
        $this->dispatch(new DeliverOrder($id));
    }
}
