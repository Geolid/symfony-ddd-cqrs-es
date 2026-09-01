<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Support\Factory;

use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\Email;
use Shared\Domain\ValueObject\PostalAddress;
use Support\ClockSequence;
use Support\Factory\AbstractAggregateTestFactory;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: CustomerId,
 *     email: Email,
 *     registeredAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateTestFactory<Customer, Attributes>
 */
final class CustomerTestFactory extends AbstractAggregateTestFactory
{
    public function withId(string $id): self
    {
        return $this->withAttributes(['id' => CustomerId::fromString($id)]);
    }

    public function withEmail(string $email): self
    {
        return $this->withAttributes(['email' => Email::fromString($email)]);
    }

    public function withRegisteredAt(\DateTimeImmutable $registeredAt): self
    {
        return $this->withAttributes(['registeredAt' => $registeredAt]);
    }

    public function shippingAddressRegistered(PostalAddress $shippingAddress, ?\DateTimeImmutable $registeredAt = null): self
    {
        $registeredAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Customer $customer) => $customer->registerShippingAddress($shippingAddress, $registeredAt),
        );
    }

    public function billingAddressRegistered(PostalAddress $billingAddress, ?\DateTimeImmutable $registeredAt = null): self
    {
        $registeredAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Customer $customer) => $customer->registerBillingAddress($billingAddress, $registeredAt),
        );
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        $erasedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (Customer $customer) => $customer->erase($erasedAt));
    }

    protected function defaults(): array
    {
        return [
            'id' => CustomerId::generate(...),
            'email' => static fn (): Email => Email::fromString(SeededFaker::get()->unique()->safeEmail()),
            'registeredAt' => ClockSequence::next(...),
        ];
    }

    protected function build(): Customer
    {
        return Customer::register(
            id: $this->attribute('id'),
            email: $this->attribute('email'),
            registeredAt: $this->attribute('registeredAt'),
        );
    }
}
