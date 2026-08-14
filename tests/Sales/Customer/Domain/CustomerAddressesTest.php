<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\CustomerAddresses;
use Sales\Customer\Domain\Event\CustomerBillingAddressSet;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Customer\Domain\Event\CustomerShippingAddressSet;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

final class CustomerAddressesTest extends AggregateRootTestCase
{
    #[Test]
    public function itSetsTheShippingAddress(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $setAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $shippingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris'));

        $this
            ->given(new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (CustomerAddresses $customerAddresses) => $customerAddresses->setShippingAddress($shippingAddress, $setAt))
            ->then(new CustomerShippingAddressSet(
                id: $id,
                address: ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
                setAt: $setAt->format(\DateTimeInterface::ATOM),
            ));
    }

    #[Test]
    public function itDoesNotSetAnIdenticalShippingAddress(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $shippingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris'));

        $this
            ->given(
                new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)),
                new CustomerShippingAddressSet($id, ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'], $registeredAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (CustomerAddresses $customerAddresses) => $customerAddresses->setShippingAddress($shippingAddress, new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itSetsTheBillingAddress(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $setAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $billingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris'));

        $this
            ->given(new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (CustomerAddresses $customerAddresses) => $customerAddresses->setBillingAddress($billingAddress, $setAt))
            ->then(new CustomerBillingAddressSet(
                id: $id,
                address: ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris'],
                setAt: $setAt->format(\DateTimeInterface::ATOM),
            ));
    }

    #[Test]
    public function itDoesNotSetAnIdenticalBillingAddress(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $billingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris'));

        $this
            ->given(
                new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)),
                new CustomerBillingAddressSet($id, ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris'], $registeredAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (CustomerAddresses $customerAddresses) => $customerAddresses->setBillingAddress($billingAddress, new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return CustomerAddresses::class;
    }
}
