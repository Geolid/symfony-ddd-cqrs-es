<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Ordering\Domain\Event\OrderAborted;
use Sales\Ordering\Domain\Event\OrderCancelled;
use Sales\Ordering\Domain\Event\OrderConfirmed;
use Sales\Ordering\Domain\Event\OrderDelivered;
use Sales\Ordering\Domain\Event\OrderDispatched;
use Sales\Ordering\Domain\Event\OrderErased;
use Sales\Ordering\Domain\Event\OrderErasureApproved;
use Sales\Ordering\Domain\Event\OrderPlaced;
use Sales\Ordering\Domain\Event\OrderPrepared;
use Sales\Ordering\Domain\Exception\OrderBelongsToAnotherBuyerException;
use Sales\Ordering\Domain\Exception\OrderNotCancellableException;
use Sales\Ordering\Domain\Exception\OrderWithoutLineException;
use Sales\Ordering\Domain\ValueObject\OrderId;
use Sales\Ordering\Domain\ValueObject\OrderLine;
use Sales\Ordering\Domain\ValueObject\OrderState;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\Specification\HasReachedSpecification;
use Shared\Domain\ValueObject\ErasureState;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('sales.ordering.order')]
final class Order implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<OrderState>> */
    private const array OPERATIONAL_TRANSITIONS = [
        OrderState::PLACED->value => [OrderState::CONFIRMED, OrderState::CANCELLED],
        OrderState::CONFIRMED->value => [OrderState::PREPARED, OrderState::CANCELLED],
        OrderState::PREPARED->value => [OrderState::DISPATCHED, OrderState::CANCELLED],
        OrderState::DISPATCHED->value => [OrderState::DELIVERED],
        OrderState::DELIVERED->value => [],
        OrderState::CANCELLED->value => [],
    ];

    /** @var array<string, list<ErasureState>> */
    private const array ERASURE_TRANSITIONS = [
        ErasureState::RETAINED->value => [ErasureState::APPROVED],
        ErasureState::APPROVED->value => [ErasureState::ERASED],
        ErasureState::ERASED->value => [],
    ];

    #[Id]
    public private(set) OrderId $id;
    public private(set) string $buyerId;
    public private(set) PostalAddress $shippingAddress;
    public private(set) PostalAddress $billingAddress;
    public private(set) int $totalAmountInCents;
    private OrderState $operationalState;
    private ErasureState $erasureState;

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
        if (!$this->canTransitionOperationalTo(OrderState::CONFIRMED)) {
            return;
        }

        $this->recordThat(new OrderConfirmed(
            id: $this->id->toString(),
            confirmedAt: $confirmedAt,
        ));
    }

    public function prepare(\DateTimeImmutable $preparedAt): void
    {
        if (!$this->canTransitionOperationalTo(OrderState::PREPARED)) {
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

        if ($this->hasReachedOperational(OrderState::CANCELLED)) {
            return;
        }

        if ($this->hasReachedOperational(OrderState::PREPARED)) {
            throw OrderNotCancellableException::forId($this->id);
        }

        $this->recordThat(new OrderCancelled(
            id: $this->id->toString(),
            cancelledAt: $cancelledAt,
        ));

        $this->tryErase($cancelledAt);
    }

    public function abort(\DateTimeImmutable $abortedAt): void
    {
        if ($this->hasReachedOperational(OrderState::CANCELLED)) {
            return;
        }

        if ($this->hasReachedOperational(OrderState::DISPATCHED)) {
            return;
        }

        $this->recordThat(new OrderAborted(
            id: $this->id->toString(),
            abortedAt: $abortedAt,
        ));

        $this->tryErase($abortedAt);
    }

    public function dispatch(\DateTimeImmutable $dispatchedAt): void
    {
        if (!$this->canTransitionOperationalTo(OrderState::DISPATCHED)) {
            return;
        }

        $this->recordThat(new OrderDispatched(
            id: $this->id->toString(),
            dispatchedAt: $dispatchedAt,
        ));
    }

    public function deliver(\DateTimeImmutable $deliveredAt): void
    {
        if (!$this->canTransitionOperationalTo(OrderState::DELIVERED)) {
            return;
        }

        $this->recordThat(new OrderDelivered(
            id: $this->id->toString(),
            deliveredAt: $deliveredAt,
        ));

        $this->tryErase($deliveredAt);
    }

    public function approveErasure(\DateTimeImmutable $approvedAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::APPROVED)) {
            return;
        }

        $this->recordThat(new OrderErasureApproved(
            id: $this->id->toString(),
            approvedAt: $approvedAt,
        ));

        $this->tryErase($approvedAt);
    }

    private function tryErase(\DateTimeImmutable $at): void
    {
        if (!$this->canErase()) {
            return;
        }

        $this->recordThat(new OrderErased(
            id: $this->id->toString(),
            erasedAt: $at,
        ));
    }

    private function canErase(): bool
    {
        return $this->erasureState->isApproved() && ($this->operationalState->isDelivered() || $this->operationalState->isCancelled());
    }

    private function canTransitionOperationalTo(OrderState $target): bool
    {
        return new CanTransitionToSpecification(self::OPERATIONAL_TRANSITIONS, $target)->isSatisfiedBy($this->operationalState);
    }

    private function hasReachedOperational(OrderState $target): bool
    {
        return new HasReachedSpecification(self::OPERATIONAL_TRANSITIONS, $target)->isSatisfiedBy($this->operationalState);
    }

    private function canTransitionErasureTo(ErasureState $target): bool
    {
        return new CanTransitionToSpecification(self::ERASURE_TRANSITIONS, $target)->isSatisfiedBy($this->erasureState);
    }

    #[Apply]
    private function applyPlaced(OrderPlaced $event): void
    {
        $this->id = OrderId::fromString($event->id);
        $this->buyerId = $event->buyerId;
        $this->shippingAddress = $event->shippingAddress;
        $this->billingAddress = $event->billingAddress;
        $this->totalAmountInCents = $event->totalAmount->cents;
        $this->operationalState = OrderState::PLACED;
        $this->erasureState = ErasureState::RETAINED;
    }

    #[Apply]
    private function applyConfirmed(OrderConfirmed $event): void
    {
        $this->operationalState = OrderState::CONFIRMED;
    }

    #[Apply]
    private function applyPrepared(OrderPrepared $event): void
    {
        $this->operationalState = OrderState::PREPARED;
    }

    #[Apply]
    private function applyCancelled(OrderCancelled $event): void
    {
        $this->operationalState = OrderState::CANCELLED;
    }

    #[Apply]
    private function applyAborted(OrderAborted $event): void
    {
        $this->operationalState = OrderState::CANCELLED;
    }

    #[Apply]
    private function applyDispatched(OrderDispatched $event): void
    {
        $this->operationalState = OrderState::DISPATCHED;
    }

    #[Apply]
    private function applyDelivered(OrderDelivered $event): void
    {
        $this->operationalState = OrderState::DELIVERED;
    }

    #[Apply]
    private function applyErasureApproved(OrderErasureApproved $event): void
    {
        $this->erasureState = ErasureState::APPROVED;
    }

    #[Apply]
    private function applyErased(OrderErased $event): void
    {
        $this->erasureState = ErasureState::ERASED;
    }
}
