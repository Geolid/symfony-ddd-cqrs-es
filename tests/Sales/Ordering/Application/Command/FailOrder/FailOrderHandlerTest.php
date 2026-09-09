<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\FailOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\FailOrder\FailOrder;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
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
        $order = OrderBuilder::new()->create();
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
        $order = OrderBuilder::new()->prepared()->create();
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
        $order = OrderBuilder::new()->failed()->create();
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
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new FailOrder($id));
    }
}
