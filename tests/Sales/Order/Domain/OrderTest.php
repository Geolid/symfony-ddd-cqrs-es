<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Shared\Domain\ValueObject\Money;

final class OrderTest extends AggregateRootTestCase
{
    #[Test]
    public function itPlacesAnOrderDerivingItsTotalFromItsLines(): void
    {
        $id = OrderId::generate();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $customerId = Uuid::uuid7()->toString();

        $this
            ->given()
            ->when(static fn () => Order::place($id, $customerId, 'buyer@example.com', self::lines(), $placedAt))
            ->then(new OrderPlaced($id->toString(), $customerId, 'buyer@example.com', self::primitiveLines(), 1_999, $placedAt->format('c')));
    }

    #[Test]
    public function itCannotPlaceAnOrderWithoutALine(): void
    {
        $id = OrderId::generate();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Order::place($id, Uuid::uuid7()->toString(), 'buyer@example.com', [], $placedAt))
            ->expectsException(OrderWithoutLineException::class);
    }

    #[Test]
    public function itCancelsAPlacedOrder(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new OrderPlaced($id, $customerId, 'buyer@example.com', self::primitiveLines(), 1_999, $placedAt->format('c')))
            ->when(static fn (Order $order) => $order->cancel($customerId, $cancelledAt))
            ->then(new OrderCancelled($id, $cancelledAt->format('c')));
    }

    #[Test]
    public function itCannotCancelAnAlreadyCancelledOrder(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new OrderPlaced($id, $customerId, 'buyer@example.com', self::primitiveLines(), 1_999, $placedAt->format('c')),
                new OrderCancelled($id, $cancelledAt->format('c')),
            )
            ->when(static fn (Order $order) => $order->cancel($customerId, new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(OrderAlreadyCancelledException::class);
    }

    #[Test]
    public function itCannotCancelAnOrderBelongingToAnotherCustomer(): void
    {
        $id = OrderId::generate()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new OrderPlaced($id, Uuid::uuid7()->toString(), 'buyer@example.com', self::primitiveLines(), 1_999, $placedAt->format('c')))
            ->when(static fn (Order $order) => $order->cancel(Uuid::uuid7()->toString(), new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(OrderBelongsToAnotherCustomerException::class);
    }

    protected function aggregateClass(): string
    {
        return Order::class;
    }

    /**
     * @return list<OrderLine>
     */
    private static function lines(): array
    {
        return [
            OrderLine::of('Espresso cups, set of 6', 1, Money::fromCents(1_750)),
            OrderLine::of('Saucer', 3, Money::fromCents(83)),
        ];
    }

    /**
     * @return list<array{label: string, quantity: int, unitAmountInCents: int}>
     */
    private static function primitiveLines(): array
    {
        return [
            ['label' => 'Espresso cups, set of 6', 'quantity' => 1, 'unitAmountInCents' => 1_750],
            ['label' => 'Saucer', 'quantity' => 3, 'unitAmountInCents' => 83],
        ];
    }
}
