<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Support\Builder;

use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\Email;
use Shared\Domain\ValueObject\PostalAddress;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: CustomerId,
 *     email: Email,
 *     registeredAt: \DateTimeImmutable,
 *     shippingAddressRegisteredAt: \DateTimeImmutable,
 *     billingAddressRegisteredAt: \DateTimeImmutable,
 *     erasedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Customer, Attributes>
 */
final class CustomerBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: CustomerId::fromString($id));
    }

    public function withEmail(string $email): self
    {
        return $this->withAttributes(email: Email::fromString($email));
    }

    public function withRegisteredAt(\DateTimeImmutable $registeredAt): self
    {
        return $this->withAttributes(registeredAt: $registeredAt);
    }

    public function shippingAddressRegistered(PostalAddress $shippingAddress, ?\DateTimeImmutable $registeredAt = null): self
    {
        $builder = null !== $registeredAt ? $this->withAttributes(shippingAddressRegisteredAt: $registeredAt) : $this;

        return $builder->withModifier(
            static fn (Customer $customer, self $builder) => $customer->registerShippingAddress($shippingAddress, $builder['shippingAddressRegisteredAt']),
        );
    }

    public function billingAddressRegistered(PostalAddress $billingAddress, ?\DateTimeImmutable $registeredAt = null): self
    {
        $builder = null !== $registeredAt ? $this->withAttributes(billingAddressRegisteredAt: $registeredAt) : $this;

        return $builder->withModifier(
            static fn (Customer $customer, self $builder) => $customer->registerBillingAddress($billingAddress, $builder['billingAddressRegisteredAt']),
        );
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        $builder = null !== $erasedAt ? $this->withAttributes(erasedAt: $erasedAt) : $this;

        return $builder->withModifier(
            static fn (Customer $customer, self $builder) => $customer->erase($builder['erasedAt']),
        );
    }

    protected static function defaults(): array
    {
        return [
            'id' => CustomerId::generate(...),
            'email' => static fn (): Email => Email::fromString(SeededFaker::get()->unique()->safeEmail()),
            'registeredAt' => static fn (): \DateTimeImmutable => Clock::get()->now(),
            'shippingAddressRegisteredAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'billingAddressRegisteredAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'erasedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+2 day'),
        ];
    }

    protected function build(): Customer
    {
        return Customer::register(
            id: $this['id'],
            email: $this['email'],
            registeredAt: $this['registeredAt'],
        );
    }
}
