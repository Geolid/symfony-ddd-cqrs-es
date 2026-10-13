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
use Crm\Tests\Customer\Support\Factory\CustomerIdFactory;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Symfony\Component\Clock\Clock;

final class CustomerTest extends AggregateRootTestCase
{
    private CustomerId $id;
    private \DateTimeImmutable $registeredAt;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $erasedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = CustomerIdFactory::new()->create();
        $now = Clock::get()->now();
        $this->registeredAt = $now;
        $this->requestedAt = $now->modify('+1 day');
        $this->cancelledAt = $now->modify('+2 days');
        $this->erasedAt = $now->modify('+2 day');
    }

    #[Test]
    public function itRegisters(): void
    {
        $this
            ->given()
            ->when(fn (): Customer => Customer::register($this->id, $this->registeredAt))
            ->then($this->registered());
    }

    #[Test]
    public function itDefinesShippingAddress(): void
    {
        $definedAt = Clock::get()->now()->modify('+1 day');
        $shippingAddress = PostalAddressFactory::new()->create();

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
        $shippingAddress = PostalAddressFactory::new()->create();
        $definedAt = Clock::get()->now()->modify('+1 day');

        $this
            ->given(
                $this->registered(),
                new CustomerShippingAddressDefined($this->id, $shippingAddress, $definedAt),
            )
            ->when(static fn (Customer $customer) => $customer->defineShippingAddress($shippingAddress, $definedAt->modify('+1 day')))
            ->then();
    }

    #[Test]
    public function itDefinesBillingAddress(): void
    {
        $definedAt = Clock::get()->now()->modify('+1 day');
        $billingAddress = PostalAddressFactory::new()->create();

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
        $billingAddress = PostalAddressFactory::new()->create();
        $definedAt = Clock::get()->now()->modify('+1 day');

        $this
            ->given(
                $this->registered(),
                new CustomerBillingAddressDefined($this->id, $billingAddress, $definedAt),
            )
            ->when(static fn (Customer $customer) => $customer->defineBillingAddress($billingAddress, $definedAt->modify('+1 day')))
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
            ->when(static fn (Customer $customer) => $customer->requestErasure(Clock::get()->now()->modify('+1 day')))
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
            ->when(static fn (Customer $customer) => $customer->cancelErasure(Clock::get()->now()->modify('+2 days')))
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
            ->when(static fn (Customer $customer) => $customer->erase(Clock::get()->now()->modify('+2 day')))
            ->then();
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $this
            ->given($this->registered(), $this->erasureRequested(), $this->erased())
            ->when(static fn (Customer $customer) => $customer->erase(Clock::get()->now()->modify('+2 day')))
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
