<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Event\CustomerBillingAddressRegistered;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Customer\Domain\Event\CustomerShippingAddressRegistered;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\Email;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

final class CustomerTest extends AggregateRootTestCase
{
    private CustomerId $id;
    private Email $email;
    private \DateTimeImmutable $registeredAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = CustomerId::generate();
        $this->email = CustomerBuilder::sample('email');
        $this->registeredAt = CustomerBuilder::sample('registeredAt');
    }

    #[Test]
    public function itRegisters(): void
    {
        $this
            ->given()
            ->when(fn (): Customer => Customer::register($this->id, $this->email, $this->registeredAt))
            ->then(new CustomerRegistered($this->id->toString(), $this->email->value, $this->registeredAt));
    }

    #[Test]
    public function itRegistersShippingAddress(): void
    {
        $setAt = CustomerBuilder::sample('shippingAddressRegisteredAt');
        $shippingAddress = $this->shippingAddress();

        $this
            ->given($this->registered())
            ->when(static fn (Customer $customer) => $customer->registerShippingAddress($shippingAddress, $setAt))
            ->then(new CustomerShippingAddressRegistered(
                id: $this->id->toString(),
                address: $this->primitiveAddress($shippingAddress),
                setAt: $setAt,
            ));
    }

    #[Test]
    public function itDoesNotRegisterWhenIdenticalShippingAddress(): void
    {
        $shippingAddress = $this->shippingAddress();
        $setAt = CustomerBuilder::sample('shippingAddressRegisteredAt');

        $this
            ->given(
                $this->registered(),
                new CustomerShippingAddressRegistered($this->id->toString(), $this->primitiveAddress($shippingAddress), $setAt),
            )
            ->when(static fn (Customer $customer) => $customer->registerShippingAddress($shippingAddress, CustomerBuilder::sample('shippingAddressRegisteredAt')))
            ->then();
    }

    #[Test]
    public function itRegistersBillingAddress(): void
    {
        $setAt = CustomerBuilder::sample('billingAddressRegisteredAt');
        $billingAddress = $this->billingAddress();

        $this
            ->given($this->registered())
            ->when(static fn (Customer $customer) => $customer->registerBillingAddress($billingAddress, $setAt))
            ->then(new CustomerBillingAddressRegistered(
                id: $this->id->toString(),
                address: $this->primitiveAddress($billingAddress),
                setAt: $setAt,
            ));
    }

    #[Test]
    public function itDoesNotRegisterWhenIdenticalBillingAddress(): void
    {
        $billingAddress = $this->billingAddress();
        $setAt = CustomerBuilder::sample('billingAddressRegisteredAt');

        $this
            ->given(
                $this->registered(),
                new CustomerBillingAddressRegistered($this->id->toString(), $this->primitiveAddress($billingAddress), $setAt),
            )
            ->when(static fn (Customer $customer) => $customer->registerBillingAddress($billingAddress, CustomerBuilder::sample('billingAddressRegisteredAt')))
            ->then();
    }

    #[Test]
    public function itErases(): void
    {
        $erasedAt = CustomerBuilder::sample('erasedAt');

        $this
            ->given($this->registered())
            ->when(static fn (Customer $customer) => $customer->erase($erasedAt))
            ->then(new CustomerErased($this->id->toString(), $erasedAt));
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $erasedAt = CustomerBuilder::sample('erasedAt');

        $this
            ->given($this->registered(), new CustomerErased($this->id->toString(), $erasedAt))
            ->when(static fn (Customer $customer) => $customer->erase(CustomerBuilder::sample('erasedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Customer::class;
    }

    private function registered(): CustomerRegistered
    {
        return new CustomerRegistered($this->id->toString(), $this->email->value, $this->registeredAt);
    }

    private function shippingAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
    }

    private function billingAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function primitiveAddress(PostalAddress $address): array
    {
        return [
            'firstName' => $address->fullName->firstName,
            'lastName' => $address->fullName->lastName,
            'street' => $address->address->street,
            'postalCode' => $address->address->postalCode,
            'city' => $address->address->city,
            'countryCode' => $address->address->countryCode->value,
        ];
    }
}
