<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Money;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\OrderId;

final class OrderTest extends AggregateRootTestCase
{
    #[Test]
    public function itPlacesAnOrder(): void
    {
        $id = OrderId::generate();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Order::place($id, 'customer-1', Money::fromCents(1_999), $placedAt))
            ->then(new OrderPlaced($id->toString(), 'customer-1', 1_999, $placedAt->format('c')));
    }

    #[Test]
    public function itCancelsAPlacedOrder(): void
    {
        $id = OrderId::generate()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new OrderPlaced($id, 'customer-1', 1_999, $placedAt->format('c')))
            ->when(static fn (Order $order) => $order->cancel($cancelledAt))
            ->then(new OrderCancelled($id, $cancelledAt->format('c')));
    }

    #[Test]
    public function itCannotCancelAnAlreadyCancelledOrder(): void
    {
        $id = OrderId::generate()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new OrderPlaced($id, 'customer-1', 1_999, $placedAt->format('c')),
                new OrderCancelled($id, $cancelledAt->format('c')),
            )
            ->when(static fn (Order $order) => $order->cancel(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(OrderAlreadyCancelledException::class);
    }

    protected function aggregateClass(): string
    {
        return Order::class;
    }
}
