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
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
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
    private OrderState $status;

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
            shippingFirstName: $shippingAddress->fullName->firstName,
            shippingLastName: $shippingAddress->fullName->lastName,
            shippingStreet: $shippingAddress->address->street,
            shippingPostalCode: $shippingAddress->address->postalCode,
            shippingCity: $shippingAddress->address->city,
            billingFirstName: $billingAddress->fullName->firstName,
            billingLastName: $billingAddress->fullName->lastName,
            billingStreet: $billingAddress->address->street,
            billingPostalCode: $billingAddress->address->postalCode,
            billingCity: $billingAddress->address->city,
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
     */
    public function cancel(string $customerId, \DateTimeImmutable $cancelledAt): void
    {
        if ($this->customerId !== $customerId) {
            throw OrderBelongsToAnotherCustomerException::forId($this->id);
        }

        if ($this->status->isCancelled()) {
            return;
        }

        $this->recordThat(new OrderCancelled(
            id: $this->id->toString(),
            cancelledAt: $cancelledAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyOrderPlaced(OrderPlaced $event): void
    {
        $this->id = OrderId::fromString($event->id);
        $this->customerId = $event->customerId;
        $this->shippingAddress = PostalAddress::of(
            FullName::of($event->shippingFirstName, $event->shippingLastName),
            Address::of($event->shippingStreet, $event->shippingPostalCode, $event->shippingCity),
        );
        $this->billingAddress = PostalAddress::of(
            FullName::of($event->billingFirstName, $event->billingLastName),
            Address::of($event->billingStreet, $event->billingPostalCode, $event->billingCity),
        );
        $this->status = OrderState::PLACED;
    }

    #[Apply]
    private function applyOrderCancelled(OrderCancelled $event): void
    {
        $this->status = OrderState::CANCELLED;
    }
}
