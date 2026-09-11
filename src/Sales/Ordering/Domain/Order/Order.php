<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Ordering\Domain\Order\Entity\Line;
use Sales\Ordering\Domain\Order\Event\OrderCancelled;
use Sales\Ordering\Domain\Order\Event\OrderConfirmed;
use Sales\Ordering\Domain\Order\Event\OrderDelivered;
use Sales\Ordering\Domain\Order\Event\OrderDispatched;
use Sales\Ordering\Domain\Order\Event\OrderErased;
use Sales\Ordering\Domain\Order\Event\OrderErasureApproved;
use Sales\Ordering\Domain\Order\Event\OrderFailed;
use Sales\Ordering\Domain\Order\Event\OrderPrepared;
use Sales\Ordering\Domain\Order\Exception\OrderBelongsToAnotherShopperException;
use Sales\Ordering\Domain\Order\Exception\OrderNotCancellableException;
use Sales\Ordering\Domain\Order\Exception\OrderWithoutLineException;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Order\ValueObject\OrderState;
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
        OrderState::CONFIRMED->value => [OrderState::PREPARED, OrderState::CANCELLED, OrderState::FAILED],
        OrderState::PREPARED->value => [OrderState::DISPATCHED, OrderState::CANCELLED, OrderState::FAILED],
        OrderState::DISPATCHED->value => [OrderState::DELIVERED],
        OrderState::DELIVERED->value => [],
        OrderState::CANCELLED->value => [],
        OrderState::FAILED->value => [],
    ];

    /** @var array<string, list<ErasureState>> */
    private const array ERASURE_TRANSITIONS = [
        ErasureState::RETAINED->value => [ErasureState::APPROVED],
        ErasureState::APPROVED->value => [ErasureState::ERASED],
        ErasureState::ERASED->value => [],
    ];

    #[Id]
    public private(set) OrderId $id;
    public private(set) string $cartId;
    public private(set) string $shopperId;
    public private(set) string $paymentId;
    public private(set) PostalAddress $shippingAddress;
    public private(set) PostalAddress $billingAddress;
    public private(set) int $totalAmountInCents;
    private OrderState $operationalState;
    private ErasureState $erasureState;

    /**
     * @param list<Line> $lines
     *
     * @throws OrderWithoutLineException
     */
    public static function confirm(
        OrderId $id,
        string $cartId,
        string $shopperId,
        string $paymentId,
        PostalAddress $shippingAddress,
        PostalAddress $billingAddress,
        array $lines,
        \DateTimeImmutable $confirmedAt,
    ): self {
        if ([] === $lines) {
            throw OrderWithoutLineException::forId($id);
        }

        $total = array_reduce(
            $lines,
            static fn (Money $carry, Line $line): Money => $carry->plus($line->total()),
            Money::fromCents(0),
        );

        $self = new self();
        $self->recordThat(new OrderConfirmed(
            id: $id->toString(),
            cartId: $cartId,
            shopperId: $shopperId,
            paymentId: $paymentId,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            lines: $lines,
            totalAmount: $total,
            confirmedAt: $confirmedAt,
        ));

        return $self;
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
     * @throws OrderBelongsToAnotherShopperException
     * @throws OrderNotCancellableException
     */
    public function cancel(string $shopperId, \DateTimeImmutable $cancelledAt): void
    {
        if ($this->shopperId !== $shopperId) {
            throw OrderBelongsToAnotherShopperException::forId($this->id);
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

    public function fail(\DateTimeImmutable $failedAt): void
    {
        if (!$this->canTransitionOperationalTo(OrderState::FAILED)) {
            return;
        }

        $this->recordThat(new OrderFailed(
            id: $this->id->toString(),
            failedAt: $failedAt,
        ));

        $this->tryErase($failedAt);
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
        return $this->erasureState->isApproved() && ($this->operationalState->isDelivered() || $this->operationalState->isCancelled() || $this->operationalState->isFailed());
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
    private function applyConfirmed(OrderConfirmed $event): void
    {
        $this->id = OrderId::fromString($event->id);
        $this->cartId = $event->cartId;
        $this->shopperId = $event->shopperId;
        $this->paymentId = $event->paymentId;
        $this->shippingAddress = $event->shippingAddress;
        $this->billingAddress = $event->billingAddress;
        $this->totalAmountInCents = $event->totalAmount->cents;
        $this->operationalState = OrderState::CONFIRMED;
        $this->erasureState = ErasureState::RETAINED;
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
    private function applyFailed(OrderFailed $event): void
    {
        $this->operationalState = OrderState::FAILED;
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
