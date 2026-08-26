<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Support\Factory;

use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\Email;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<Customer>
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

    public function withShippingAddress(PostalAddress $shippingAddress, ?\DateTimeImmutable $registeredAt = null): self
    {
        $registeredAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Customer $customer) => $customer->registerShippingAddress($shippingAddress, $registeredAt),
        );
    }

    public function withBillingAddress(PostalAddress $billingAddress, ?\DateTimeImmutable $registeredAt = null): self
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
            'email' => self::faker()->unique()->safeEmail(),
            'registeredAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
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
