<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Domain\Customer;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\Event\CustomerBillingAddressDefined;
use Crm\Customer\Domain\Customer\Event\CustomerErased;
use Crm\Customer\Domain\Customer\Event\CustomerErasureCancelled;
use Crm\Customer\Domain\Customer\Event\CustomerErasureRequested;
use Crm\Customer\Domain\Customer\Event\CustomerRegistered;
use Crm\Customer\Domain\Customer\Event\CustomerShippingAddressDefined;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Crm\Customer\Domain\Customer\ValueObject\Email;
use Crm\Customer\Domain\Customer\ValueObject\Name;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;

final class CustomerTest extends AggregateRootTestCase
{
    private CustomerId $id;
    private Name $firstName;
    private Name $lastName;
    private Email $email;
    private \DateTimeImmutable $registeredAt;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $erasedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = CustomerId::fromString(Uuid::uuid7()->toString());
        $this->firstName = CustomerBuilder::sample('firstName');
        $this->lastName = CustomerBuilder::sample('lastName');
        $this->email = CustomerBuilder::sample('email');
        $this->registeredAt = CustomerBuilder::sample('registeredAt');
        $this->requestedAt = CustomerBuilder::sample('requestedAt');
        $this->cancelledAt = CustomerBuilder::sample('cancelledAt');
        $this->erasedAt = CustomerBuilder::sample('erasedAt');
    }

    #[Test]
    public function itRegisters(): void
    {
        $this
            ->given()
            ->when(fn (): Customer => Customer::register(
                $this->id,
                $this->firstName,
                $this->lastName,
                $this->email,
                $this->registeredAt,
            ))
            ->then($this->registered());
    }

    #[Test]
    public function itDefinesShippingAddress(): void
    {
        $definedAt = CustomerBuilder::sample('shippingAddressDefinedAt');
        $shippingAddress = CustomerBuilder::sample('shippingAddress');

        $this
            ->given($this->registered())
            ->when(static fn (Customer $customer) => $customer->defineShippingAddress($shippingAddress, $definedAt))
            ->then(new CustomerShippingAddressDefined(
                id: $this->id,
                postalAddress: $shippingAddress,
                definedAt: $definedAt,
            ));
    }

    #[Test]
    public function itDoesNotDefineWhenIdenticalShippingAddress(): void
    {
        $shippingAddress = CustomerBuilder::sample('shippingAddress');
        $definedAt = CustomerBuilder::sample('shippingAddressDefinedAt');

        $this
            ->given(
                $this->registered(),
                new CustomerShippingAddressDefined($this->id, $shippingAddress, $definedAt),
            )
            ->when(static fn (Customer $customer) => $customer->defineShippingAddress($shippingAddress, CustomerBuilder::sample('shippingAddressDefinedAt')))
            ->then();
    }

    #[Test]
    public function itDefinesBillingAddress(): void
    {
        $definedAt = CustomerBuilder::sample('billingAddressDefinedAt');
        $billingAddress = CustomerBuilder::sample('billingAddress');

        $this
            ->given($this->registered())
            ->when(static fn (Customer $customer) => $customer->defineBillingAddress($billingAddress, $definedAt))
            ->then(new CustomerBillingAddressDefined(
                id: $this->id,
                postalAddress: $billingAddress,
                definedAt: $definedAt,
            ));
    }

    #[Test]
    public function itDoesNotDefineWhenIdenticalBillingAddress(): void
    {
        $billingAddress = CustomerBuilder::sample('billingAddress');
        $definedAt = CustomerBuilder::sample('billingAddressDefinedAt');

        $this
            ->given(
                $this->registered(),
                new CustomerBillingAddressDefined($this->id, $billingAddress, $definedAt),
            )
            ->when(static fn (Customer $customer) => $customer->defineBillingAddress($billingAddress, CustomerBuilder::sample('billingAddressDefinedAt')))
            ->then();
    }

    #[Test]
    public function itRequestsErasure(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Customer $customer) => $customer->requestErasure($this->requestedAt))
            ->then(new CustomerErasureRequested($this->id, $this->requestedAt));
    }

    #[Test]
    public function itDoesNotRequestErasureWhenAlreadyRequested(): void
    {
        $this
            ->given($this->registered(), $this->erasureRequested())
            ->when(static fn (Customer $customer) => $customer->requestErasure(CustomerBuilder::sample('requestedAt')))
            ->then();
    }

    #[Test]
    public function itCancelsErasure(): void
    {
        $this
            ->given($this->registered(), $this->erasureRequested())
            ->when(fn (Customer $customer) => $customer->cancelErasure($this->cancelledAt))
            ->then(new CustomerErasureCancelled($this->id, $this->cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelErasureWhenRetained(): void
    {
        $this
            ->given($this->registered())
            ->when(static fn (Customer $customer) => $customer->cancelErasure(CustomerBuilder::sample('cancelledAt')))
            ->then();
    }

    #[Test]
    public function itErases(): void
    {
        $this
            ->given($this->registered(), $this->erasureRequested())
            ->when(fn (Customer $customer) => $customer->erase($this->erasedAt))
            ->then(new CustomerErased($this->id, $this->erasedAt));
    }

    #[Test]
    public function itDoesNotEraseWhenRetained(): void
    {
        $this
            ->given($this->registered())
            ->when(static fn (Customer $customer) => $customer->erase(CustomerBuilder::sample('erasedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $this
            ->given($this->registered(), $this->erasureRequested(), $this->erased())
            ->when(static fn (Customer $customer) => $customer->erase(CustomerBuilder::sample('erasedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Customer::class;
    }

    private function registered(): CustomerRegistered
    {
        return new CustomerRegistered(
            id: $this->id,
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $this->email,
            registeredAt: $this->registeredAt,
        );
    }

    private function erasureRequested(): CustomerErasureRequested
    {
        return new CustomerErasureRequested($this->id, $this->requestedAt);
    }

    private function erased(): CustomerErased
    {
        return new CustomerErased($this->id, $this->erasedAt);
    }
}
