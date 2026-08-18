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
use Sales\Customer\Domain\Exception\CustomerAlreadyErasedException;
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
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn (): Customer => Customer::register($id, Email::fromString('Buyer@Example.COM'), $registeredAt))
            ->then(new CustomerRegistered($id->toString(), 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itRegistersShippingAddress(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $setAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $shippingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris'));

        $this
            ->given(new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Customer $customer) => $customer->registerShippingAddress($shippingAddress, $setAt))
            ->then(new CustomerShippingAddressRegistered(
                id: $id,
                address: ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
                setAt: $setAt->format(\DateTimeInterface::ATOM),
            ));
    }

    #[Test]
    public function itDoesNotRegisterWhenIdenticalShippingAddress(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $shippingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris'));

        $this
            ->given(
                new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)),
                new CustomerShippingAddressRegistered($id, ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'], $registeredAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Customer $customer) => $customer->registerShippingAddress($shippingAddress, new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itRegistersBillingAddress(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $setAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $billingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris'));

        $this
            ->given(new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Customer $customer) => $customer->registerBillingAddress($billingAddress, $setAt))
            ->then(new CustomerBillingAddressRegistered(
                id: $id,
                address: ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris'],
                setAt: $setAt->format(\DateTimeInterface::ATOM),
            ));
    }

    #[Test]
    public function itDoesNotRegisterWhenIdenticalBillingAddress(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $billingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris'));

        $this
            ->given(
                new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)),
                new CustomerBillingAddressRegistered($id, ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris'], $registeredAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Customer $customer) => $customer->registerBillingAddress($billingAddress, new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itErases(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Customer $customer) => $customer->erase($erasedAt))
            ->then(new CustomerErased($id, $erasedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)),
                new CustomerErased($id, $erasedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Customer $customer) => $customer->erase(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itCannotRegisterAddressWhenErased(): void
    {
        $id = CustomerId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $shippingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris'));

        $this
            ->given(
                new CustomerRegistered($id, 'buyer@example.com', $registeredAt->format(\DateTimeInterface::ATOM)),
                new CustomerErased($id, $erasedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Customer $customer) => $customer->registerShippingAddress($shippingAddress, new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(CustomerAlreadyErasedException::class);
    }

    protected function aggregateClass(): string
    {
        return Customer::class;
    }
}
