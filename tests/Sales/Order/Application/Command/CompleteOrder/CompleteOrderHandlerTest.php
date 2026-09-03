<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CompleteOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\CompleteOrder\CompleteOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotCompletableException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CompleteOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itCompletesWhenReturnWindowHasElapsed(): void
    {
        // Given
        $order = OrderBuilder::new()
            ->confirmed()
            ->dispatched()
            ->delivered(Clock::get()->now()->modify('-19 days'))
            ->create();
        $this->store($order);

        // When
        $this->dispatch(new CompleteOrder($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::COMPLETED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyCompleted(): void
    {
        // Given
        $order = OrderBuilder::new()
            ->confirmed()
            ->dispatched()
            ->delivered(Clock::get()->now()->modify('-19 days'))
            ->completed()
            ->create();
        $this->store($order);

        // When
        $this->dispatch(new CompleteOrder($order->id->toString()));

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
        $this->dispatch(new CompleteOrder($id));
    }

    #[Test]
    public function itFailsWhenNotCompletable(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->dispatched()->create();
        $this->store($order);

        // Then
        $this->expectException(OrderNotCompletableException::class);

        // When
        $this->dispatch(new CompleteOrder($order->id->toString()));
    }
}
