<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Event\OrderBillingAddressErased;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;

final class OrderTest extends AggregateRootTestCase
{
    #[Test]
    public function itPlacesDerivingItsTotalFromItsLines(): void
    {
        $id = OrderId::generate();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $customerId = Uuid::uuid7()->toString();
        $lines = self::lines();

        $this
            ->given()
            ->when(static fn () => Order::place($id, $customerId, self::shippingAddress(), self::billingAddress(), $lines, $placedAt))
            ->then(new OrderPlaced(
                $id->toString(),
                $customerId,
                self::primitiveShippingAddress(),
                self::primitiveBillingAddress(),
                self::primitiveLines($lines),
                1_999,
                $placedAt->format(\DateTimeInterface::ATOM),
            ));
    }

    #[Test]
    public function itCannotPlaceWithoutALine(): void
    {
        $id = OrderId::generate();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Order::place($id, Uuid::uuid7()->toString(), self::shippingAddress(), self::billingAddress(), [], $placedAt))
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
            ->given(self::orderPlaced($id, $customerId, $placedAt))
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
                self::orderPlaced($id, $customerId, $placedAt),
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
            ->given(self::orderPlaced($id, Uuid::uuid7()->toString(), $placedAt))
            ->when(static fn (Order $order) => $order->cancel(Uuid::uuid7()->toString(), new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(OrderBelongsToAnotherCustomerException::class);
    }

    #[Test]
    public function itErasesTheBillingAddress(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(self::orderPlaced($id, $customerId, $placedAt))
            ->when(static fn (Order $order) => $order->eraseBillingAddress($erasedAt))
            ->then(new OrderBillingAddressErased($id, $erasedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotReeraseAnAlreadyErasedBillingAddress(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                self::orderPlaced($id, $customerId, $placedAt),
                new OrderBillingAddressErased($id, $erasedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->eraseBillingAddress(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
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
            OrderLine::of(Product::of(Uuid::uuid7()->toString(), Label::fromString('Espresso cups, set of 6'), Money::fromCents(1_750)), 1),
            OrderLine::of(Product::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83)), 3),
        ];
    }

    /**
     * @param list<OrderLine> $lines
     *
     * @return list<array{productId: string, label: string, quantity: int, unitAmountInCents: int}>
     */
    private static function primitiveLines(array $lines): array
    {
        return array_map(
            static fn (OrderLine $line): array => [
                'productId' => $line->product->id,
                'label' => $line->product->label->toString(),
                'quantity' => $line->quantity,
                'unitAmountInCents' => $line->product->price->toCents(),
            ],
            $lines,
        );
    }

    private static function shippingAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris'));
    }

    private static function billingAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris'));
    }

    private static function orderPlaced(string $id, string $customerId, \DateTimeImmutable $placedAt): OrderPlaced
    {
        return new OrderPlaced(
            $id,
            $customerId,
            self::primitiveShippingAddress(),
            self::primitiveBillingAddress(),
            self::primitiveLines(self::lines()),
            1_999,
            $placedAt->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string}
     */
    private static function primitiveShippingAddress(): array
    {
        return ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'];
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string}
     */
    private static function primitiveBillingAddress(): array
    {
        return ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris'];
    }
}
