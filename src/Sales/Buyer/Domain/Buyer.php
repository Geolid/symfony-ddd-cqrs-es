<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Buyer\Domain\Event\BuyerBillingAddressRegistered;
use Sales\Buyer\Domain\Event\BuyerErased;
use Sales\Buyer\Domain\Event\BuyerRegistered;
use Sales\Buyer\Domain\Event\BuyerShippingAddressRegistered;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\Email;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('sales.buyer.buyer')]
final class Buyer implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) BuyerId $id;
    public private(set) Email $email;
    public private(set) ?PostalAddress $shippingAddress = null;
    public private(set) ?PostalAddress $billingAddress = null;
    private bool $erased;

    public static function register(BuyerId $id, Email $email, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new BuyerRegistered(
            id: $id->toString(),
            email: $email->value,
            registeredAt: $registeredAt,
        ));

        return $self;
    }

    public function registerShippingAddress(PostalAddress $shippingAddress, \DateTimeImmutable $registeredAt): void
    {
        if (true === $this->shippingAddress?->equals($shippingAddress)) {
            return;
        }

        $this->recordThat(new BuyerShippingAddressRegistered(
            id: $this->id->toString(),
            address: $shippingAddress->toArray(),
            setAt: $registeredAt,
        ));
    }

    public function registerBillingAddress(PostalAddress $billingAddress, \DateTimeImmutable $registeredAt): void
    {
        if (true === $this->billingAddress?->equals($billingAddress)) {
            return;
        }

        $this->recordThat(new BuyerBillingAddressRegistered(
            id: $this->id->toString(),
            address: $billingAddress->toArray(),
            setAt: $registeredAt,
        ));
    }

    public function erase(\DateTimeImmutable $erasedAt): void
    {
        if ($this->erased) {
            return;
        }

        $this->recordThat(new BuyerErased(
            id: $this->id->toString(),
            erasedAt: $erasedAt,
        ));
    }

    #[Apply]
    private function applyRegistered(BuyerRegistered $event): void
    {
        $this->id = BuyerId::fromString($event->id);
        $this->email = Email::fromString($event->email);
        $this->erased = false;
    }

    #[Apply]
    private function applyShippingAddressRegistered(BuyerShippingAddressRegistered $event): void
    {
        $this->shippingAddress = $this->toAddress($event->address);
    }

    #[Apply]
    private function applyBillingAddressRegistered(BuyerBillingAddressRegistered $event): void
    {
        $this->billingAddress = $this->toAddress($event->address);
    }

    #[Apply]
    private function applyErased(BuyerErased $event): void
    {
        $this->erased = true;
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
