<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Event\OrderAnonymized;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderCompleted;
use Sales\Order\Domain\Event\OrderConfirmed;
use Sales\Order\Domain\Event\OrderDelivered;
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Event\OrderReturned;
use Sales\Order\Domain\Event\OrderReturnRejected;
use Sales\Order\Domain\Event\OrderReturnRequested;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderNotCancellableException;
use Sales\Order\Domain\Exception\OrderNotCompletableException;
use Sales\Order\Domain\Exception\OrderNotReturnableException;
use Sales\Order\Domain\Exception\OrderReturnWindowExpiredException;
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
    public function itPlacesDerivingTotalFromLines(): void
    {
        $id = OrderId::generate();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $customerId = Uuid::uuid7()->toString();
        $lines = $this->lines();

        $this
            ->given()
            ->when(static fn (): Order => Order::place($id, $customerId, self::shippingAddress(), self::billingAddress(), $lines, $now))
            ->then(new OrderPlaced(
                $id->toString(),
                $customerId,
                $this->primitiveShippingAddress(),
                $this->primitiveBillingAddress(),
                $this->primitiveLines($lines),
                1_999,
                $now,
            ));
    }

    #[Test]
    public function itCannotPlaceWithoutLine(): void
    {
        $id = OrderId::generate();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn (): Order => Order::place($id, Uuid::uuid7()->toString(), self::shippingAddress(), self::billingAddress(), [], $now))
            ->expectsException(OrderWithoutLineException::class);
    }

    #[Test]
    public function itConfirmsWhenPlaced(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');

        $this
            ->given($this->orderPlaced($id, $customerId, $now))
            ->when(static fn (Order $order) => $order->confirm($confirmedAt))
            ->then(new OrderConfirmed($id, $confirmedAt));
    }

    #[Test]
    public function itDoesNotConfirmWhenAlreadyConfirmed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
            )
            ->when(static fn (Order $order) => $order->confirm($confirmedAt->modify('+1 hour')))
            ->then();
    }

    #[Test]
    public function itCancels(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = $now->modify('+1 day');

        $this
            ->given($this->orderPlaced($id, $customerId, $now))
            ->when(static fn (Order $order) => $order->cancel($customerId, $cancelledAt))
            ->then(new OrderCancelled($id, $cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenAlreadyCancelled(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = $now->modify('+1 day');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderCancelled($id, $cancelledAt),
            )
            ->when(static fn (Order $order) => $order->cancel($customerId, $cancelledAt->modify('+1 day')))
            ->then();
    }

    #[Test]
    public function itCancelsWhenConfirmed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $cancelledAt = $now->modify('+1 day');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
            )
            ->when(static fn (Order $order) => $order->cancel($customerId, $cancelledAt))
            ->then(new OrderCancelled($id, $cancelledAt));
    }

    #[Test]
    public function itCannotCancelWhenDispatched(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
            )
            ->when(static fn (Order $order) => $order->cancel($customerId, $dispatchedAt->modify('+1 day')))
            ->expectsException(OrderNotCancellableException::class);
    }

    #[Test]
    public function itCannotCancelWhenBelongingToAnotherCustomer(): void
    {
        $id = OrderId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given($this->orderPlaced($id, Uuid::uuid7()->toString(), $now))
            ->when(static fn (Order $order) => $order->cancel(Uuid::uuid7()->toString(), $now->modify('+1 day')))
            ->expectsException(OrderBelongsToAnotherCustomerException::class);
    }

    #[Test]
    public function itDispatchesWhenConfirmed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
            )
            ->when(static fn (Order $order) => $order->dispatch($dispatchedAt))
            ->then(new OrderDispatched($id, $dispatchedAt));
    }

    #[Test]
    public function itDoesNotDispatchWhenNotConfirmed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given($this->orderPlaced($id, $customerId, $now))
            ->when(static fn (Order $order) => $order->dispatch($now->modify('+2 days')))
            ->then();
    }

    #[Test]
    public function itDeliversWhenDispatched(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
            )
            ->when(static fn (Order $order) => $order->deliver($deliveredAt))
            ->then(new OrderDelivered($id, $deliveredAt));
    }

    #[Test]
    public function itDoesNotDeliverWhenNotDispatched(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
            )
            ->when(static fn (Order $order) => $order->deliver($now->modify('+2 days')))
            ->then();
    }

    #[Test]
    public function itCompletesWhenReturnWindowElapsed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $completedAt = $deliveredAt->modify('+30 days');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
                new OrderDelivered($id, $deliveredAt),
            )
            ->when(static fn (Order $order) => $order->complete($completedAt))
            ->then(new OrderCompleted($id, $completedAt));
    }

    #[Test]
    public function itDoesNotCompleteWhenReturnWindowNotElapsed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
                new OrderDelivered($id, $deliveredAt),
            )
            ->when(static fn (Order $order) => $order->complete($deliveredAt->modify('+6 days')))
            ->then();
    }

    #[Test]
    public function itDoesNotCompleteWhenAlreadyCompleted(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $completedAt = $deliveredAt->modify('+30 days');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
                new OrderDelivered($id, $deliveredAt),
                new OrderCompleted($id, $completedAt),
            )
            ->when(static fn (Order $order) => $order->complete($completedAt->modify('+30 days')))
            ->then();
    }

    #[Test]
    public function itCannotCompleteWhenNotCompletable(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
            )
            ->when(static fn (Order $order) => $order->complete($confirmedAt->modify('+30 days')))
            ->expectsException(OrderNotCompletableException::class);
    }

    #[Test]
    public function itRequestsReturnWhenDelivered(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $deliveredAt->modify('+6 days');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
                new OrderDelivered($id, $deliveredAt),
            )
            ->when(static fn (Order $order) => $order->requestReturn($customerId, $requestedAt))
            ->then(new OrderReturnRequested($id, $requestedAt));
    }

    #[Test]
    public function itDoesNotRequestReturnWhenAlreadyRequested(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $deliveredAt->modify('+6 days');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
                new OrderDelivered($id, $deliveredAt),
                new OrderReturnRequested($id, $requestedAt),
            )
            ->when(static fn (Order $order) => $order->requestReturn($customerId, $requestedAt->modify('+1 day')))
            ->then();
    }

    #[Test]
    public function itCannotRequestReturnWhenBelongingToAnotherCustomer(): void
    {
        $id = OrderId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                $this->orderPlaced($id, Uuid::uuid7()->toString(), $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
                new OrderDelivered($id, $deliveredAt),
            )
            ->when(static fn (Order $order) => $order->requestReturn(Uuid::uuid7()->toString(), $deliveredAt->modify('+6 days')))
            ->expectsException(OrderBelongsToAnotherCustomerException::class);
    }

    #[Test]
    public function itCannotRequestReturnWhenNotDelivered(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
            )
            ->when(static fn (Order $order) => $order->requestReturn($customerId, $confirmedAt->modify('+6 days')))
            ->expectsException(OrderNotReturnableException::class);
    }

    #[Test]
    public function itCannotRequestReturnWhenReturnWindowElapsed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
                new OrderDelivered($id, $deliveredAt),
            )
            ->when(static fn (Order $order) => $order->requestReturn($customerId, $deliveredAt->modify('+15 days')))
            ->expectsException(OrderReturnWindowExpiredException::class);
    }

    #[Test]
    public function itConfirmsReturnWhenRequested(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $deliveredAt->modify('+6 days');
        $returnedAt = $requestedAt->modify('+2 days');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
                new OrderDelivered($id, $deliveredAt),
                new OrderReturnRequested($id, $requestedAt),
            )
            ->when(static fn (Order $order) => $order->confirmReturn($returnedAt))
            ->then(new OrderReturned($id, $returnedAt));
    }

    #[Test]
    public function itDoesNotConfirmReturnWhenNotRequested(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
                new OrderDelivered($id, $deliveredAt),
            )
            ->when(static fn (Order $order) => $order->confirmReturn($deliveredAt->modify('+6 days')))
            ->then();
    }

    #[Test]
    public function itRejectsReturnWhenRequested(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $deliveredAt->modify('+6 days');
        $rejectedAt = $requestedAt->modify('+1 day');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
                new OrderDelivered($id, $deliveredAt),
                new OrderReturnRequested($id, $requestedAt),
            )
            ->when(static fn (Order $order) => $order->rejectReturn('item damaged beyond resale', $rejectedAt))
            ->then(new OrderReturnRejected($id, 'item damaged beyond resale', $rejectedAt));
    }

    #[Test]
    public function itDoesNotRejectReturnWhenNotRequested(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = $now->modify('+2 hours');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderConfirmed($id, $confirmedAt),
                new OrderDispatched($id, $dispatchedAt),
                new OrderDelivered($id, $deliveredAt),
            )
            ->when(static fn (Order $order) => $order->rejectReturn('item damaged beyond resale', $deliveredAt->modify('+6 days')))
            ->then();
    }

    #[Test]
    public function itEnsuresNotCancelled(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given($this->orderPlaced($id, $customerId, $now))
            ->when(static fn (Order $order) => $order->ensureNotCancelled())
            ->then();
    }

    #[Test]
    public function itCannotEnsureNotCancelledWhenCancelled(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = $now->modify('+1 day');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderCancelled($id, $cancelledAt),
            )
            ->when(static fn (Order $order) => $order->ensureNotCancelled())
            ->expectsException(OrderAlreadyCancelledException::class);
    }

    #[Test]
    public function itAnonymizesWhenRetentionPeriodElapsed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = $now->modify('+1 day');
        $anonymizedAt = $now->modify('+11 years');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderCancelled($id, $cancelledAt),
            )
            ->when(static fn (Order $order) => $order->anonymize($anonymizedAt))
            ->then(new OrderAnonymized($id, $anonymizedAt));
    }

    #[Test]
    public function itDoesNotAnonymizeWhenNotClosed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given($this->orderPlaced($id, $customerId, $now))
            ->when(static fn (Order $order) => $order->anonymize($now->modify('+11 years')))
            ->then();
    }

    #[Test]
    public function itDoesNotAnonymizeWhenRetentionPeriodNotElapsed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = $now->modify('+1 day');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderCancelled($id, $cancelledAt),
            )
            ->when(static fn (Order $order) => $order->anonymize($cancelledAt->modify('+1 month')))
            ->then();
    }

    #[Test]
    public function itDoesNotAnonymizeWhenAlreadyAnonymized(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = $now->modify('+1 day');
        $anonymizedAt = $now->modify('+11 years');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $now),
                new OrderCancelled($id, $cancelledAt),
                new OrderAnonymized($id, $anonymizedAt),
            )
            ->when(static fn (Order $order) => $order->anonymize($anonymizedAt->modify('+1 day')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Order::class;
    }

    /**
     * @return list<OrderLine>
     */
    private function lines(): array
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
    private function primitiveLines(array $lines): array
    {
        return array_map(
            static fn (OrderLine $line): array => [
                'productId' => $line->product->id,
                'label' => $line->product->label->value,
                'quantity' => $line->quantity,
                'unitAmountInCents' => $line->product->price->cents,
            ],
            $lines,
        );
    }

    private static function shippingAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
    }

    private static function billingAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));
    }

    private function orderPlaced(string $id, string $customerId, \DateTimeImmutable $placedAt): OrderPlaced
    {
        return new OrderPlaced(
            $id,
            $customerId,
            $this->primitiveShippingAddress(),
            $this->primitiveBillingAddress(),
            $this->primitiveLines($this->lines()),
            1_999,
            $placedAt,
        );
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function primitiveShippingAddress(): array
    {
        return ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR'];
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function primitiveBillingAddress(): array
    {
        return ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris', 'countryCode' => 'FR'];
    }
}
