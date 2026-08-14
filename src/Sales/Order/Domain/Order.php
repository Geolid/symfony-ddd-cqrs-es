<?php

declare(strict_types=1);

namespace Sales\Order\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Order\Domain\Event\OrderBillingAddressErased;
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
    private bool $billingAddressErased;

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

    public function eraseBillingAddress(\DateTimeImmutable $erasedAt): void
    {
        if ($this->billingAddressErased) {
            return;
        }

        $this->recordThat(new OrderBillingAddressErased(
            id: $this->id->toString(),
            erasedAt: $erasedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyOrderPlaced(OrderPlaced $event): void
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
        $this->status = OrderState::PLACED;
        $this->billingAddressErased = false;
    }

    #[Apply]
    private function applyOrderCancelled(OrderCancelled $event): void
    {
        $this->status = OrderState::CANCELLED;
    }

    #[Apply]
    private function applyOrderBillingAddressErased(OrderBillingAddressErased $event): void
    {
        $this->billingAddressErased = true;
    }
}
