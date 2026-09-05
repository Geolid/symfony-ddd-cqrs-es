<?php

declare(strict_types=1);

namespace Sales\Order\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Order\Domain\Event\OrderAborted;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderConfirmed;
use Sales\Order\Domain\Event\OrderDelivered;
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Order\Domain\Event\OrderDisputed;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Event\OrderPrepared;
use Sales\Order\Domain\Event\OrderReturned;
use Sales\Order\Domain\Event\OrderReturnRequested;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherBuyerException;
use Sales\Order\Domain\Exception\OrderNotCancellableException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\OrderState;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\Specification\HasReachedSpecification;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('sales.order.order')]
final class Order implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<OrderState>> */
    private const array TRANSITIONS = [
        OrderState::PLACED->value => [OrderState::CONFIRMED, OrderState::CANCELLED],
        OrderState::CONFIRMED->value => [OrderState::PREPARED, OrderState::CANCELLED],
        OrderState::PREPARED->value => [OrderState::DISPATCHED, OrderState::CANCELLED],
        OrderState::DISPATCHED->value => [OrderState::DELIVERED],
        OrderState::DELIVERED->value => [OrderState::RETURN_REQUESTED],
        OrderState::RETURN_REQUESTED->value => [OrderState::RETURNED, OrderState::DISPUTED],
        OrderState::CANCELLED->value => [],
        OrderState::RETURNED->value => [],
        OrderState::DISPUTED->value => [],
    ];

    #[Id]
    public private(set) OrderId $id;
    public private(set) string $buyerId;
    public private(set) PostalAddress $shippingAddress;
    public private(set) PostalAddress $billingAddress;
    public private(set) int $totalAmountInCents;
    private OrderState $state;

    /**
     * @param list<OrderLine> $lines
     *
     * @throws OrderWithoutLineException
     */
    public static function place(
        OrderId $id,
        string $buyerId,
        PostalAddress $shippingAddress,
        PostalAddress $billingAddress,
        array $lines,
        \DateTimeImmutable $placedAt,
    ): self {
        if ([] === $lines) {
            throw OrderWithoutLineException::forId($id);
        }

        $total = array_reduce(
            $lines,
            static fn (Money $carry, OrderLine $line): Money => $carry->plus($line->total()),
            Money::fromCents(0),
        );

        $self = new self();
        $self->recordThat(new OrderPlaced(
            id: $id->toString(),
            buyerId: $buyerId,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            lines: $lines,
            totalAmount: $total,
            placedAt: $placedAt,
        ));

        return $self;
    }

    public function confirm(\DateTimeImmutable $confirmedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, OrderState::CONFIRMED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new OrderConfirmed(
            id: $this->id->toString(),
            confirmedAt: $confirmedAt,
        ));
    }

    public function prepare(\DateTimeImmutable $preparedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, OrderState::PREPARED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new OrderPrepared(
            id: $this->id->toString(),
            preparedAt: $preparedAt,
        ));
    }

    /**
     * @throws OrderBelongsToAnotherBuyerException
     * @throws OrderNotCancellableException
     */
    public function cancel(string $buyerId, \DateTimeImmutable $cancelledAt): void
    {
        if ($this->buyerId !== $buyerId) {
            throw OrderBelongsToAnotherBuyerException::forId($this->id);
        }

        if (new HasReachedSpecification(self::TRANSITIONS, OrderState::CANCELLED)->isSatisfiedBy($this->state)) {
            return;
        }

        if (new HasReachedSpecification(self::TRANSITIONS, OrderState::PREPARED)->isSatisfiedBy($this->state)) {
            throw OrderNotCancellableException::forId($this->id);
        }

        $this->recordThat(new OrderCancelled(
            id: $this->id->toString(),
            cancelledAt: $cancelledAt,
        ));
    }

    public function abort(\DateTimeImmutable $abortedAt): void
    {
        if (new HasReachedSpecification(self::TRANSITIONS, OrderState::CANCELLED)->isSatisfiedBy($this->state)) {
            return;
        }

        if (new HasReachedSpecification(self::TRANSITIONS, OrderState::DISPATCHED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new OrderAborted(
            id: $this->id->toString(),
            abortedAt: $abortedAt,
        ));
    }

    public function dispatch(\DateTimeImmutable $dispatchedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, OrderState::DISPATCHED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new OrderDispatched(
            id: $this->id->toString(),
            dispatchedAt: $dispatchedAt,
        ));
    }

    public function deliver(\DateTimeImmutable $deliveredAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, OrderState::DELIVERED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new OrderDelivered(
            id: $this->id->toString(),
            deliveredAt: $deliveredAt,
        ));
    }

    public function requestReturn(\DateTimeImmutable $requestedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, OrderState::RETURN_REQUESTED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new OrderReturnRequested(
            id: $this->id->toString(),
            requestedAt: $requestedAt,
        ));
    }

    public function return(\DateTimeImmutable $returnedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, OrderState::RETURNED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new OrderReturned(
            id: $this->id->toString(),
            returnedAt: $returnedAt,
        ));
    }

    public function dispute(\DateTimeImmutable $disputedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, OrderState::DISPUTED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new OrderDisputed(
            id: $this->id->toString(),
            disputedAt: $disputedAt,
        ));
    }

    #[Apply]
    private function applyPlaced(OrderPlaced $event): void
    {
        $this->id = OrderId::fromString($event->id);
        $this->buyerId = $event->buyerId;
        $this->shippingAddress = $event->shippingAddress;
        $this->billingAddress = $event->billingAddress;
        $this->totalAmountInCents = $event->totalAmount->cents;
        $this->state = OrderState::PLACED;
    }

    #[Apply]
    private function applyConfirmed(OrderConfirmed $event): void
    {
        $this->state = OrderState::CONFIRMED;
    }

    #[Apply]
    private function applyPrepared(OrderPrepared $event): void
    {
        $this->state = OrderState::PREPARED;
    }

    #[Apply]
    private function applyCancelled(OrderCancelled $event): void
    {
        $this->state = OrderState::CANCELLED;
    }

    #[Apply]
    private function applyAborted(OrderAborted $event): void
    {
        $this->state = OrderState::CANCELLED;
    }

    #[Apply]
    private function applyDispatched(OrderDispatched $event): void
    {
        $this->state = OrderState::DISPATCHED;
    }

    #[Apply]
    private function applyDelivered(OrderDelivered $event): void
    {
        $this->state = OrderState::DELIVERED;
    }

    #[Apply]
    private function applyReturnRequested(OrderReturnRequested $event): void
    {
        $this->state = OrderState::RETURN_REQUESTED;
    }

    #[Apply]
    private function applyReturned(OrderReturned $event): void
    {
        $this->state = OrderState::RETURNED;
    }

    #[Apply]
    private function applyDisputed(OrderDisputed $event): void
    {
        $this->state = OrderState::DISPUTED;
    }
}
