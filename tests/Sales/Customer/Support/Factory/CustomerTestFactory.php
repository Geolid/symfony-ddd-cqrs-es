<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Support\Factory;

use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\Email;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<Customer>
 */
final class CustomerTestFactory extends AbstractAggregateTestFactory
{
    public function withEmail(string $email): self
    {
        return static::new(array_merge($this->attributes, ['email' => $email]));
    }

    public function erased(): self
    {
        return $this->withModifier(static fn (Customer $customer) => $customer->erase(new \DateTimeImmutable('now +00:00')));
    }

    public function linkedToIdentity(string $identityId): self
    {
        return $this->withModifier(static fn (Customer $customer) => $customer->linkIdentity($identityId));
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
