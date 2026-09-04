<?php

declare(strict_types=1);

namespace Sales\Order\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderCompleted;
use Sales\Order\Domain\Event\OrderConfirmed;
use Sales\Order\Domain\Event\OrderDelivered;
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderNotCancellableException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\OrderState;
use Shared\Domain\ValueObject\Address;
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
            shippingAddress: $shippingAddress->toArray(),
            billingAddress: $billingAddress->toArray(),
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

        $this->recordThat(new OrderCompleted(
            id: $this->id->toString(),
            completedAt: $deliveredAt,
        ));
    }

    #[Apply]
    private function applyPlaced(OrderPlaced $event): void
    {
        $this->id = OrderId::fromString($event->id);
        $this->customerId = $event->customerId;
        $this->shippingAddress = $this->toAddress($event->shippingAddress);
        $this->billingAddress = $this->toAddress($event->billingAddress);
        $this->totalAmountInCents = $event->totalAmountInCents;
        $this->state = OrderState::PLACED;
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
    private function applyCompleted(OrderCompleted $event): void
    {
        $this->state = OrderState::COMPLETED;
    }

    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $address
     */
    private function toAddress(array $address): PostalAddress
    {
        return PostalAddress::of(
            $address['recipientName'],
            Address::of($address['street'], $address['postalCode'], $address['city'], $address['countryCode']),
        );
    }
}
