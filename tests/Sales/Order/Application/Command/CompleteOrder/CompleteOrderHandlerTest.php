<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CompleteOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\CompleteOrder\CompleteOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotCompletableException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\MockClock;

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
        self::getContainer()->set('clock', new MockClock('2026-01-20T00:00:00+00:00'));
        $order = OrderTestFactory::new()
            ->confirmed()
            ->dispatched()
            ->delivered(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->store();

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
        self::getContainer()->set('clock', new MockClock('2026-01-20T00:00:00+00:00'));
        $order = OrderTestFactory::new()
            ->confirmed()
            ->dispatched()
            ->delivered(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->completed(new \DateTimeImmutable('2026-01-20T00:00:00+00:00'))
            ->store();

        // When
        $this->dispatch(new CompleteOrder($order->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotCompletable(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->store();

        // Then
        $this->expectException(OrderNotCompletableException::class);

        // When
        $this->dispatch(new CompleteOrder($order->id->toString()));
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
}
