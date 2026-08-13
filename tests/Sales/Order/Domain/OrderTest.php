<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

final class OrderTest extends AggregateRootTestCase
{
    private const string CUPS_ID = '01977b1e-0000-7000-8000-000000000001';
    private const string SAUCER_ID = '01977b1e-0000-7000-8000-000000000002';

    #[Test]
    public function itPlacesDerivingItsTotalFromItsLines(): void
    {
        $id = OrderId::generate();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $customerId = Uuid::uuid7()->toString();

        $this
            ->given()
            ->when(static fn () => Order::place($id, $customerId, 'buyer@example.com', self::lines(), $placedAt))
            ->then(new OrderPlaced($id->toString(), $customerId, 'buyer@example.com', self::primitiveLines(), 1_999, $placedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotPlaceWithoutALine(): void
    {
        $id = OrderId::generate();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Order::place($id, Uuid::uuid7()->toString(), 'buyer@example.com', [], $placedAt))
            ->expectsException(OrderWithoutLineException::class);
    }

    #[Test]
    public function itCancels(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new OrderPlaced($id, $customerId, 'buyer@example.com', self::primitiveLines(), 1_999, $placedAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Order $order) => $order->cancel($customerId, $cancelledAt))
            ->then(new OrderCancelled($id, $cancelledAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotCancelAnAlreadyCancelled(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new OrderPlaced($id, $customerId, 'buyer@example.com', self::primitiveLines(), 1_999, $placedAt->format(\DateTimeInterface::ATOM)),
                new OrderCancelled($id, $cancelledAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->cancel($customerId, new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itCannotCancelWhenBelongingToAnotherCustomer(): void
    {
        $id = OrderId::generate()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new OrderPlaced($id, Uuid::uuid7()->toString(), 'buyer@example.com', self::primitiveLines(), 1_999, $placedAt->format(\DateTimeInterface::ATOM)))
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
            OrderLine::of(Product::of(self::CUPS_ID, Label::fromString('Espresso cups, set of 6'), Money::fromCents(1_750)), 1),
            OrderLine::of(Product::of(self::SAUCER_ID, Label::fromString('Saucer'), Money::fromCents(83)), 3),
        ];
    }

    /**
     * @return list<array{productId: string, label: string, quantity: int, unitAmountInCents: int}>
     */
    private static function primitiveLines(): array
    {
        return [
            ['productId' => self::CUPS_ID, 'label' => 'Espresso cups, set of 6', 'quantity' => 1, 'unitAmountInCents' => 1_750],
            ['productId' => self::SAUCER_ID, 'label' => 'Saucer', 'quantity' => 3, 'unitAmountInCents' => 83],
        ];
    }
}
