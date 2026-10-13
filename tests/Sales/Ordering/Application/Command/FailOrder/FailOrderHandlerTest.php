<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\FailOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\Command\FailOrder\FailOrder;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Sales\Tests\Ordering\Support\Factory\OrderIdFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class FailOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itFailsWhenConfirmed(): void
    {
        // Given
        $order = OrderFactory::new()->create();
        $this->store($order);

        // When
        $this->dispatch(new FailOrder($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::FAILED, $result->status);
    }

    #[Test]
    public function itFailsWhenPrepared(): void
    {
        // Given
        $order = OrderFactory::new()->prepared()->create();
        $this->store($order);

        // When
        $this->dispatch(new FailOrder($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::FAILED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyFailed(): void
    {
        // Given
        $order = OrderFactory::new()->failed()->create();
        $this->store($order);

        // When
        $this->dispatch(new FailOrder($order->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = OrderIdFactory::new()->create()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new FailOrder($id));
    }
}
