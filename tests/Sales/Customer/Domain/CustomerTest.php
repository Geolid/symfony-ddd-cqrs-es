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
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

final class CustomerTest extends AggregateRootTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        $id = CustomerId::generate();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn (): Customer => Customer::register($id, Email::fromString('Buyer@Example.COM'), $now))
            ->then(new CustomerRegistered($id->toString(), 'buyer@example.com', $now));
    }

    #[Test]
    public function itRegistersShippingAddress(): void
    {
        $id = CustomerId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $setAt = $now->modify('+1 day');
        $shippingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));

        $this
            ->given(new CustomerRegistered($id, 'buyer@example.com', $now))
            ->when(static fn (Customer $customer) => $customer->registerShippingAddress($shippingAddress, $setAt))
            ->then(new CustomerShippingAddressRegistered(
                id: $id,
                address: ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR'],
                setAt: $setAt,
            ));
    }

    #[Test]
    public function itDoesNotRegisterWhenIdenticalShippingAddress(): void
    {
        $id = CustomerId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $shippingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));

        $this
            ->given(
                new CustomerRegistered($id, 'buyer@example.com', $now),
                new CustomerShippingAddressRegistered($id, ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR'], $now),
            )
            ->when(static fn (Customer $customer) => $customer->registerShippingAddress($shippingAddress, $now->modify('+2 days')))
            ->then();
    }

    #[Test]
    public function itRegistersBillingAddress(): void
    {
        $id = CustomerId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $setAt = $now->modify('+1 day');
        $billingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));

        $this
            ->given(new CustomerRegistered($id, 'buyer@example.com', $now))
            ->when(static fn (Customer $customer) => $customer->registerBillingAddress($billingAddress, $setAt))
            ->then(new CustomerBillingAddressRegistered(
                id: $id,
                address: ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris', 'countryCode' => 'FR'],
                setAt: $setAt,
            ));
    }

    #[Test]
    public function itDoesNotRegisterWhenIdenticalBillingAddress(): void
    {
        $id = CustomerId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $billingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));

        $this
            ->given(
                new CustomerRegistered($id, 'buyer@example.com', $now),
                new CustomerBillingAddressRegistered($id, ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris', 'countryCode' => 'FR'], $now),
            )
            ->when(static fn (Customer $customer) => $customer->registerBillingAddress($billingAddress, $now->modify('+2 days')))
            ->then();
    }

    #[Test]
    public function itErases(): void
    {
        $id = CustomerId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = $now->modify('+1 day');

        $this
            ->given(new CustomerRegistered($id, 'buyer@example.com', $now))
            ->when(static fn (Customer $customer) => $customer->erase($erasedAt))
            ->then(new CustomerErased($id, $erasedAt));
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $id = CustomerId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(
                new CustomerRegistered($id, 'buyer@example.com', $now),
                new CustomerErased($id, $now->modify('+1 day')),
            )
            ->when(static fn (Customer $customer) => $customer->erase($now->modify('+2 days')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Customer::class;
    }
}
