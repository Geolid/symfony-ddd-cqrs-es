<?php

declare(strict_types=1);

namespace Sales\Order\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
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
use Sales\Order\Domain\Specification\RetentionExpiredSpecification;
use Sales\Order\Domain\Specification\ReturnWindowExpiredSpecification;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\OrderState;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('sales.order.order')]
final class Order implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) OrderId $id;
    public private(set) string $customerId;
    public private(set) PostalAddress $shippingAddress;
    public private(set) PostalAddress $billingAddress;
    public private(set) int $totalAmountInCents;
    private OrderState $state;
    private \DateTimeImmutable $deliveredAt;
    private ?\DateTimeImmutable $closedAt = null;
    private ?\DateTimeImmutable $anonymizedAt = null;

    /**
     * @throws OrderAlreadyCancelledException
     */
    public function ensureNotCancelled(): void
    {
        if ($this->state->isCancelled()) {
            throw OrderAlreadyCancelledException::forId($this->id);
        }
    }

    /**
     * @param list<OrderLine> $lines
     *
     * @throws OrderWithoutLineException
     */
    public static function place(
        OrderId $id,
        string $customerId,
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
            customerId: $customerId,
            shippingAddress: [
                'firstName' => $shippingAddress->fullName->firstName,
                'lastName' => $shippingAddress->fullName->lastName,
                'street' => $shippingAddress->address->street,
                'postalCode' => $shippingAddress->address->postalCode,
                'city' => $shippingAddress->address->city,
                'countryCode' => $shippingAddress->address->countryCode->value,
            ],
            billingAddress: [
                'firstName' => $billingAddress->fullName->firstName,
                'lastName' => $billingAddress->fullName->lastName,
                'street' => $billingAddress->address->street,
                'postalCode' => $billingAddress->address->postalCode,
                'city' => $billingAddress->address->city,
                'countryCode' => $billingAddress->address->countryCode->value,
            ],
            lines: array_values(array_map(
                static fn (OrderLine $line): array => [
                    'productId' => $line->product->id,
                    'label' => $line->product->label->value,
                    'quantity' => $line->quantity,
                    'unitAmountInCents' => $line->product->price->cents,
                ],
                $lines,
            )),
            totalAmountInCents: $total->cents,
            placedAt: $placedAt,
        ));

        return $self;
    }

    public function confirm(\DateTimeImmutable $confirmedAt): void
    {
        if (!$this->state->isPlaced()) {
            return;
        }

        $this->recordThat(new OrderConfirmed(
            id: $this->id->toString(),
            confirmedAt: $confirmedAt,
        ));
    }

    /**
     * @throws OrderBelongsToAnotherCustomerException
     * @throws OrderNotCancellableException
     */
    public function cancel(string $customerId, \DateTimeImmutable $cancelledAt): void
    {
        if ($this->customerId !== $customerId) {
            throw OrderBelongsToAnotherCustomerException::forId($this->id);
        }

        if ($this->state->isCancelled()) {
            return;
        }

        if (!$this->state->isCancellable()) {
            throw OrderNotCancellableException::forId($this->id);
        }

        $this->recordThat(new OrderCancelled(
            id: $this->id->toString(),
            cancelledAt: $cancelledAt,
        ));
    }

    public function dispatch(\DateTimeImmutable $dispatchedAt): void
    {
        if (!$this->state->isConfirmed()) {
            return;
        }

        $this->recordThat(new OrderDispatched(
            id: $this->id->toString(),
            dispatchedAt: $dispatchedAt,
        ));
    }

    public function deliver(\DateTimeImmutable $deliveredAt): void
    {
        if (!$this->state->isDispatched()) {
            return;
        }

        $this->recordThat(new OrderDelivered(
            id: $this->id->toString(),
            deliveredAt: $deliveredAt,
        ));
    }

    /**
     * @throws OrderNotCompletableException
     */
    public function complete(\DateTimeImmutable $now): void
    {
        if ($this->state->isCompleted()) {
            return;
        }

        if (!$this->state->isDelivered()) {
            throw OrderNotCompletableException::forId($this->id);
        }

        if (!new ReturnWindowExpiredSpecification($now)->isSatisfiedBy($this->deliveredAt)) {
            return;
        }

        $this->recordThat(new OrderCompleted(
            id: $this->id->toString(),
            completedAt: $now,
        ));
    }

    /**
     * @throws OrderBelongsToAnotherCustomerException
     * @throws OrderNotReturnableException
     * @throws OrderReturnWindowExpiredException
     */
    public function requestReturn(string $customerId, \DateTimeImmutable $now): void
    {
        if ($this->customerId !== $customerId) {
            throw OrderBelongsToAnotherCustomerException::forId($this->id);
        }

        if ($this->state->isReturnRequested()) {
            return;
        }

        if (!$this->state->isDelivered()) {
            throw OrderNotReturnableException::forId($this->id);
        }

        if (new ReturnWindowExpiredSpecification($now)->isSatisfiedBy($this->deliveredAt)) {
            throw OrderReturnWindowExpiredException::forId($this->id);
        }

        $this->recordThat(new OrderReturnRequested(
            id: $this->id->toString(),
            requestedAt: $now,
        ));
    }

    public function confirmReturn(\DateTimeImmutable $returnedAt): void
    {
        if (!$this->state->isReturnRequested()) {
            return;
        }

        $this->recordThat(new OrderReturned(
            id: $this->id->toString(),
            returnedAt: $returnedAt,
        ));
    }

    public function rejectReturn(string $reason, \DateTimeImmutable $rejectedAt): void
    {
        if (!$this->state->isReturnRequested()) {
            return;
        }

        $this->recordThat(new OrderReturnRejected(
            id: $this->id->toString(),
            reason: $reason,
            rejectedAt: $rejectedAt,
        ));
    }

    public function anonymize(\DateTimeImmutable $now): void
    {
        if (null !== $this->anonymizedAt) {
            return;
        }

        if (null === $this->closedAt || !new RetentionExpiredSpecification($now)->isSatisfiedBy($this->closedAt)) {
            return;
        }

        $this->recordThat(new OrderAnonymized(
            id: $this->id->toString(),
            anonymizedAt: $now,
        ));
    }

    #[Apply]
    private function applyPlaced(OrderPlaced $event): void
    {
        $this->id = OrderId::fromString($event->id);
        $this->customerId = $event->customerId;
        $this->shippingAddress = PostalAddress::of(
            FullName::of($event->shippingAddress['firstName'], $event->shippingAddress['lastName']),
            Address::of($event->shippingAddress['street'], $event->shippingAddress['postalCode'], $event->shippingAddress['city'], $event->shippingAddress['countryCode']),
        );
        $this->billingAddress = PostalAddress::of(
            FullName::of($event->billingAddress['firstName'], $event->billingAddress['lastName']),
            Address::of($event->billingAddress['street'], $event->billingAddress['postalCode'], $event->billingAddress['city'], $event->billingAddress['countryCode']),
        );
        $this->totalAmountInCents = $event->totalAmountInCents;
        $this->state = OrderState::PLACED;
        $this->closedAt = null;
        $this->anonymizedAt = null;
    }

    #[Apply]
    private function applyConfirmed(OrderConfirmed $event): void
    {
        $this->state = OrderState::CONFIRMED;
    }

    #[Apply]
    private function applyCancelled(OrderCancelled $event): void
    {
        $this->state = OrderState::CANCELLED;
        $this->closedAt = $event->cancelledAt;
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
        $this->deliveredAt = $event->deliveredAt;
    }

    #[Apply]
    private function applyCompleted(OrderCompleted $event): void
    {
        $this->state = OrderState::COMPLETED;
        $this->closedAt = $event->completedAt;
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
        $this->closedAt = $event->returnedAt;
    }

    #[Apply]
    private function applyReturnRejected(OrderReturnRejected $event): void
    {
        $this->state = OrderState::RETURN_REJECTED;
        $this->closedAt = $event->rejectedAt;
    }

    #[Apply]
    private function applyAnonymized(OrderAnonymized $event): void
    {
        $this->anonymizedAt = $event->anonymizedAt;
    }
}
