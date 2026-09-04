<?php

declare(strict_types=1);

namespace Sales\Customer\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Customer\Domain\Event\CustomerBillingAddressRegistered;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Customer\Domain\Event\CustomerShippingAddressRegistered;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\Email;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('sales.customer.customer')]
final class Customer implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) CustomerId $id;
    public private(set) Email $email;
    public private(set) ?PostalAddress $shippingAddress = null;
    public private(set) ?PostalAddress $billingAddress = null;
    private bool $erased;

    public static function register(CustomerId $id, Email $email, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new CustomerRegistered(
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

        $this->recordThat(new CustomerShippingAddressRegistered(
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

        $this->recordThat(new CustomerBillingAddressRegistered(
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

        $this->recordThat(new CustomerErased(
            id: $this->id->toString(),
            erasedAt: $erasedAt,
        ));
    }

    #[Apply]
    private function applyRegistered(CustomerRegistered $event): void
    {
        $this->id = CustomerId::fromString($event->id);
        $this->email = Email::fromString($event->email);
        $this->erased = false;
    }

    #[Apply]
    private function applyShippingAddressRegistered(CustomerShippingAddressRegistered $event): void
    {
        $this->shippingAddress = $this->toAddress($event->address);
    }

    #[Apply]
    private function applyBillingAddressRegistered(CustomerBillingAddressRegistered $event): void
    {
        $this->billingAddress = $this->toAddress($event->address);
    }

    #[Apply]
    private function applyErased(CustomerErased $event): void
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
