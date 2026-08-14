<?php

declare(strict_types=1);

namespace Sales\Customer\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Patchlevel\EventSourcing\Attribute\SharedApplyContext;
use Patchlevel\EventSourcing\Attribute\Stream;
use Sales\Customer\Domain\Event\CustomerBillingAddressSet;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Customer\Domain\Event\CustomerShippingAddressSet;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('sales.customer.addresses')]
#[Stream(Customer::class)]
#[SharedApplyContext([Customer::class])]
final class CustomerAddresses implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private CustomerId $id;
    private ?PostalAddress $shippingAddress = null;
    private ?PostalAddress $billingAddress = null;

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function shippingAddress(): ?PostalAddress
    {
        return $this->shippingAddress;
    }

    public function billingAddress(): ?PostalAddress
    {
        return $this->billingAddress;
    }

    public function setShippingAddress(PostalAddress $shippingAddress, \DateTimeImmutable $setAt): void
    {
        if (null !== $this->shippingAddress && $this->shippingAddress->equals($shippingAddress)) {
            return;
        }

        $this->recordThat(new CustomerShippingAddressSet(
            id: $this->id->toString(),
            address: [
                'firstName' => $shippingAddress->fullName->firstName,
                'lastName' => $shippingAddress->fullName->lastName,
                'street' => $shippingAddress->address->street,
                'postalCode' => $shippingAddress->address->postalCode,
                'city' => $shippingAddress->address->city,
            ],
            setAt: $setAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function setBillingAddress(PostalAddress $billingAddress, \DateTimeImmutable $setAt): void
    {
        if (null !== $this->billingAddress && $this->billingAddress->equals($billingAddress)) {
            return;
        }

        $this->recordThat(new CustomerBillingAddressSet(
            id: $this->id->toString(),
            address: [
                'firstName' => $billingAddress->fullName->firstName,
                'lastName' => $billingAddress->fullName->lastName,
                'street' => $billingAddress->address->street,
                'postalCode' => $billingAddress->address->postalCode,
                'city' => $billingAddress->address->city,
            ],
            setAt: $setAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyCustomerRegistered(CustomerRegistered $event): void
    {
        $this->id = CustomerId::fromString($event->id);
    }

    #[Apply]
    private function applyCustomerShippingAddressSet(CustomerShippingAddressSet $event): void
    {
        $this->shippingAddress = PostalAddress::of(
            FullName::of($event->address['firstName'], $event->address['lastName']),
            Address::of($event->address['street'], $event->address['postalCode'], $event->address['city']),
        );
    }

    #[Apply]
    private function applyCustomerBillingAddressSet(CustomerBillingAddressSet $event): void
    {
        $this->billingAddress = PostalAddress::of(
            FullName::of($event->address['firstName'], $event->address['lastName']),
            Address::of($event->address['street'], $event->address['postalCode'], $event->address['city']),
        );
    }
}
