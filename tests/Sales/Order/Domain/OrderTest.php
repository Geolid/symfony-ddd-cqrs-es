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
use Sales\Order\Domain\Service\RetentionPolicy;
use Sales\Order\Domain\Service\ReturnWindowPolicy;
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
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $customerId = Uuid::uuid7()->toString();
        $lines = self::lines();

        $this
            ->given()
            ->when(static fn (): Order => Order::place($id, $customerId, self::shippingAddress(), self::billingAddress(), $lines, $placedAt))
            ->then(new OrderPlaced(
                $id->toString(),
                $customerId,
                $this->primitiveShippingAddress(),
                $this->primitiveBillingAddress(),
                self::primitiveLines($lines),
                1_999,
                $placedAt->format(\DateTimeInterface::ATOM),
            ));
    }

    #[Test]
    public function itCannotPlaceWithoutLine(): void
    {
        $id = OrderId::generate();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn (): Order => Order::place($id, Uuid::uuid7()->toString(), self::shippingAddress(), self::billingAddress(), [], $placedAt))
            ->expectsException(OrderWithoutLineException::class);
    }

    #[Test]
    public function itConfirmsWhenPlaced(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given($this->orderPlaced($id, $customerId, $placedAt))
            ->when(static fn (Order $order) => $order->confirm($confirmedAt))
            ->then(new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotConfirmWhenAlreadyConfirmed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->confirm(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itCancels(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given($this->orderPlaced($id, $customerId, $placedAt))
            ->when(static fn (Order $order) => $order->cancel($customerId, $cancelledAt))
            ->then(new OrderCancelled($id, $cancelledAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotCancelWhenAlreadyCancelled(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderCancelled($id, $cancelledAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->cancel($customerId, new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itCancelsWhenConfirmed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->cancel($customerId, $cancelledAt))
            ->then(new OrderCancelled($id, $cancelledAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotCancelWhenDispatched(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->cancel($customerId, new \DateTimeImmutable('2026-01-04T00:00:00+00:00')))
            ->expectsException(OrderNotCancellableException::class);
    }

    #[Test]
    public function itCannotCancelWhenBelongingToAnotherCustomer(): void
    {
        $id = OrderId::generate()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given($this->orderPlaced($id, Uuid::uuid7()->toString(), $placedAt))
            ->when(static fn (Order $order) => $order->cancel(Uuid::uuid7()->toString(), new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(OrderBelongsToAnotherCustomerException::class);
    }

    #[Test]
    public function itDispatchesWhenConfirmed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->dispatch($dispatchedAt))
            ->then(new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotDispatchWhenNotConfirmed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given($this->orderPlaced($id, $customerId, $placedAt))
            ->when(static fn (Order $order) => $order->dispatch(new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itDeliversWhenDispatched(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->deliver($deliveredAt))
            ->then(new OrderDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotDeliverWhenNotDispatched(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->deliver(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itCompletesWhenReturnWindowElapsed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');
        $completedAt = new \DateTimeImmutable('2026-02-01T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new OrderDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->complete($completedAt, new ReturnWindowPolicy(14)))
            ->then(new OrderCompleted($id, $completedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotCompleteWhenReturnWindowNotElapsed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new OrderDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->complete(new \DateTimeImmutable('2026-01-10T00:00:00+00:00'), new ReturnWindowPolicy(14)))
            ->then();
    }

    #[Test]
    public function itDoesNotCompleteWhenAlreadyCompleted(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');
        $completedAt = new \DateTimeImmutable('2026-02-01T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new OrderDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)),
                new OrderCompleted($id, $completedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->complete(new \DateTimeImmutable('2026-03-01T00:00:00+00:00'), new ReturnWindowPolicy(14)))
            ->then();
    }

    #[Test]
    public function itCannotCompleteWhenNotCompletable(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->complete(new \DateTimeImmutable('2026-02-01T00:00:00+00:00'), new ReturnWindowPolicy(14)))
            ->expectsException(OrderNotCompletableException::class);
    }

    #[Test]
    public function itRequestsReturnWhenDelivered(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');
        $requestedAt = new \DateTimeImmutable('2026-01-10T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new OrderDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->requestReturn($customerId, $requestedAt, new ReturnWindowPolicy(14)))
            ->then(new OrderReturnRequested($id, $requestedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotRequestReturnWhenAlreadyRequested(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');
        $requestedAt = new \DateTimeImmutable('2026-01-10T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new OrderDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)),
                new OrderReturnRequested($id, $requestedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->requestReturn($customerId, new \DateTimeImmutable('2026-01-11T00:00:00+00:00'), new ReturnWindowPolicy(14)))
            ->then();
    }

    #[Test]
    public function itCannotRequestReturnWhenBelongingToAnotherCustomer(): void
    {
        $id = OrderId::generate()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, Uuid::uuid7()->toString(), $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new OrderDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->requestReturn(Uuid::uuid7()->toString(), new \DateTimeImmutable('2026-01-10T00:00:00+00:00'), new ReturnWindowPolicy(14)))
            ->expectsException(OrderBelongsToAnotherCustomerException::class);
    }

    #[Test]
    public function itCannotRequestReturnWhenNotDelivered(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->requestReturn($customerId, new \DateTimeImmutable('2026-01-10T00:00:00+00:00'), new ReturnWindowPolicy(14)))
            ->expectsException(OrderNotReturnableException::class);
    }

    #[Test]
    public function itCannotRequestReturnWhenReturnWindowElapsed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new OrderDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->requestReturn($customerId, new \DateTimeImmutable('2026-01-19T00:00:00+00:00'), new ReturnWindowPolicy(14)))
            ->expectsException(OrderReturnWindowExpiredException::class);
    }

    #[Test]
    public function itConfirmsReturnWhenRequested(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');
        $requestedAt = new \DateTimeImmutable('2026-01-10T00:00:00+00:00');
        $returnedAt = new \DateTimeImmutable('2026-01-12T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new OrderDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)),
                new OrderReturnRequested($id, $requestedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->confirmReturn($returnedAt))
            ->then(new OrderReturned($id, $returnedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotConfirmReturnWhenNotRequested(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new OrderDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->confirmReturn(new \DateTimeImmutable('2026-01-11T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itRejectsReturnWhenRequested(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');
        $requestedAt = new \DateTimeImmutable('2026-01-10T00:00:00+00:00');
        $rejectedAt = new \DateTimeImmutable('2026-01-11T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new OrderDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)),
                new OrderReturnRequested($id, $requestedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->rejectReturn('item damaged beyond resale', $rejectedAt))
            ->then(new OrderReturnRejected($id, 'item damaged beyond resale', $rejectedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotRejectReturnWhenNotRequested(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $confirmedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderConfirmed($id, $confirmedAt->format(\DateTimeInterface::ATOM)),
                new OrderDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new OrderDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->rejectReturn('item damaged beyond resale', new \DateTimeImmutable('2026-01-11T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itEnsuresNotCancelled(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given($this->orderPlaced($id, $customerId, $placedAt))
            ->when(static fn (Order $order) => $order->ensureNotCancelled())
            ->then();
    }

    #[Test]
    public function itCannotEnsureNotCancelledWhenCancelled(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderCancelled($id, $cancelledAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->ensureNotCancelled())
            ->expectsException(OrderAlreadyCancelledException::class);
    }

    #[Test]
    public function itAnonymizesWhenRetentionPeriodElapsed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2016-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2016-01-02T00:00:00+00:00');
        $now = new \DateTimeImmutable('2026-02-01T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderCancelled($id, $cancelledAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->anonymize($now, new RetentionPolicy(3650)))
            ->then(new OrderAnonymized($id, $now->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotAnonymizeWhenNotClosed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2016-01-01T00:00:00+00:00');

        $this
            ->given($this->orderPlaced($id, $customerId, $placedAt))
            ->when(static fn (Order $order) => $order->anonymize(new \DateTimeImmutable('2026-02-01T00:00:00+00:00'), new RetentionPolicy(3650)))
            ->then();
    }

    #[Test]
    public function itDoesNotAnonymizeWhenRetentionPeriodNotElapsed(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderCancelled($id, $cancelledAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->anonymize(new \DateTimeImmutable('2026-02-01T00:00:00+00:00'), new RetentionPolicy(3650)))
            ->then();
    }

    #[Test]
    public function itDoesNotAnonymizeWhenAlreadyAnonymized(): void
    {
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();
        $placedAt = new \DateTimeImmutable('2016-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2016-01-02T00:00:00+00:00');
        $anonymizedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                $this->orderPlaced($id, $customerId, $placedAt),
                new OrderCancelled($id, $cancelledAt->format(\DateTimeInterface::ATOM)),
                new OrderAnonymized($id, $anonymizedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Order $order) => $order->anonymize(new \DateTimeImmutable('2026-02-01T00:00:00+00:00'), new RetentionPolicy(3650)))
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
                'label' => $line->product->label->value,
                'quantity' => $line->quantity,
                'unitAmountInCents' => $line->product->price->cents,
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

    private function orderPlaced(string $id, string $customerId, \DateTimeImmutable $placedAt): OrderPlaced
    {
        return new OrderPlaced(
            $id,
            $customerId,
            $this->primitiveShippingAddress(),
            $this->primitiveBillingAddress(),
            self::primitiveLines(self::lines()),
            1_999,
            $placedAt->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string}
     */
    private function primitiveShippingAddress(): array
    {
        return ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'];
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string}
     */
    private function primitiveBillingAddress(): array
    {
        return ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris'];
    }
}
