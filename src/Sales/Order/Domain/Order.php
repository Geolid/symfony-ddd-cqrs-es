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
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('sales.order.order')]
final class Order implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private OrderId $id;
    private string $customerId;
    private PostalAddress $shippingAddress;
    private PostalAddress $billingAddress;
    private int $totalAmountInCents;
    private OrderState $state;
    private ?\DateTimeImmutable $anonymizedAt;

    public function id(): OrderId
    {
        return $this->id;
    }

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function shippingAddress(): PostalAddress
    {
        return $this->shippingAddress;
    }

    public function billingAddress(): PostalAddress
    {
        return $this->billingAddress;
    }

    public function totalAmountInCents(): int
    {
        return $this->totalAmountInCents;
    }

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
            ],
            billingAddress: [
                'firstName' => $billingAddress->fullName->firstName,
                'lastName' => $billingAddress->fullName->lastName,
                'street' => $billingAddress->address->street,
                'postalCode' => $billingAddress->address->postalCode,
                'city' => $billingAddress->address->city,
            ],
            lines: array_values(array_map(
                static fn (OrderLine $line): array => [
                    'productId' => $line->product->id,
                    'label' => $line->product->label->toString(),
                    'quantity' => $line->quantity,
                    'unitAmountInCents' => $line->product->price->toCents(),
                ],
                $lines,
            )),
            totalAmountInCents: $total->toCents(),
            placedAt: $placedAt->format(\DateTimeInterface::ATOM),
        ));

        return $self;
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
            cancelledAt: $cancelledAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function confirm(\DateTimeImmutable $confirmedAt): void
    {
        if (!$this->state->isPlaced()) {
            return;
        }

        $this->recordThat(new OrderConfirmed(
            id: $this->id->toString(),
            confirmedAt: $confirmedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function dispatch(\DateTimeImmutable $dispatchedAt): void
    {
        if (!$this->state->isConfirmed()) {
            return;
        }

        $this->recordThat(new OrderDispatched(
            id: $this->id->toString(),
            dispatchedAt: $dispatchedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function complete(\DateTimeImmutable $completedAt): void
    {
        if (!$this->state->isDispatched()) {
            return;
        }

        $this->recordThat(new OrderCompleted(
            id: $this->id->toString(),
            completedAt: $completedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function anonymize(\DateTimeImmutable $anonymizedAt): void
    {
        if (null !== $this->anonymizedAt) {
            return;
        }

        $this->recordThat(new OrderAnonymized(
            id: $this->id->toString(),
            anonymizedAt: $anonymizedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyPlaced(OrderPlaced $event): void
    {
        $this->id = OrderId::fromString($event->id);
        $this->customerId = $event->customerId;
        $this->shippingAddress = PostalAddress::of(
            FullName::of($event->shippingAddress['firstName'], $event->shippingAddress['lastName']),
            Address::of($event->shippingAddress['street'], $event->shippingAddress['postalCode'], $event->shippingAddress['city']),
        );
        $this->billingAddress = PostalAddress::of(
            FullName::of($event->billingAddress['firstName'], $event->billingAddress['lastName']),
            Address::of($event->billingAddress['street'], $event->billingAddress['postalCode'], $event->billingAddress['city']),
        );
        $this->totalAmountInCents = $event->totalAmountInCents;
        $this->state = OrderState::PLACED;
        $this->anonymizedAt = null;
    }

    #[Apply]
    private function applyCancelled(OrderCancelled $event): void
    {
        $this->state = OrderState::CANCELLED;
    }

    #[Apply]
    private function applyConfirmed(OrderConfirmed $event): void
    {
        $this->state = OrderState::CONFIRMED;
    }

    #[Apply]
    private function applyDispatched(OrderDispatched $event): void
    {
        $this->state = OrderState::DISPATCHED;
    }

    #[Apply]
    private function applyCompleted(OrderCompleted $event): void
    {
        $this->state = OrderState::COMPLETED;
    }

    #[Apply]
    private function applyAnonymized(OrderAnonymized $event): void
    {
        $this->anonymizedAt = new \DateTimeImmutable($event->anonymizedAt);
    }
}
