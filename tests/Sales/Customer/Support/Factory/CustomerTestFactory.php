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
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: string,
 *     email: string,
 *     registeredAt: \DateTimeInterface,
 * }
 *
 * @extends AbstractAggregateTestFactory<Customer, Attributes>
 */
final class CustomerTestFactory extends AbstractAggregateTestFactory
{
    public function withId(string $id): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['id' => $id]));
    }

    public function withEmail(string $email): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['email' => $email]));
    }

    public function withRegisteredAt(\DateTimeImmutable $registeredAt): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['registeredAt' => $registeredAt]));
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
            'id' => CustomerId::generate()->toString(),
            'email' => SeededFaker::get()->unique()->safeEmail(),
            'registeredAt' => ClockSequence::next(),
        ];
    }

    protected function build(array $attributes): Customer
    {
        Assert::stringNotEmpty($id = $attributes['id']);
        Assert::stringNotEmpty($email = $attributes['email']);
        Assert::isInstanceOf($registeredAt = $attributes['registeredAt'], \DateTimeInterface::class);

        return Customer::register(
            CustomerId::fromString($id),
            Email::fromString($email),
            \DateTimeImmutable::createFromInterface($registeredAt),
        );
    }
}
