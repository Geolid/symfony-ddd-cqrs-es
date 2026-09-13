<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Support\Builder;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Crm\Customer\Domain\Customer\ValueObject\Email;
use Crm\Customer\Domain\Customer\ValueObject\Name;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: CustomerId,
 *     identityId: string,
 *     firstName: Name,
 *     lastName: Name,
 *     email: Email,
 *     registeredAt: \DateTimeImmutable,
 *     shippingAddress: PostalAddress,
 *     shippingAddressDefinedAt: \DateTimeImmutable,
 *     billingAddress: PostalAddress,
 *     billingAddressDefinedAt: \DateTimeImmutable,
 *     requestedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     erasedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Customer, Attributes>
 */
final class CustomerBuilder extends AbstractAggregateBuilder
{
    public function withFirstName(string $firstName): self
    {
        return $this->withAttributes(firstName: Name::fromString($firstName));
    }

    public function withLastName(string $lastName): self
    {
        return $this->withAttributes(lastName: Name::fromString($lastName));
    }

    public function withEmail(string $email): self
    {
        return $this->withAttributes(email: Email::fromString($email));
    }

    public function withRegisteredAt(\DateTimeImmutable $registeredAt): self
    {
        return $this->withAttributes(registeredAt: $registeredAt);
    }

    public function shippingAddressDefined(?PostalAddress $shippingAddress = null, ?\DateTimeImmutable $definedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter(
            ['shippingAddress' => $shippingAddress, 'shippingAddressDefinedAt' => $definedAt],
            static fn (mixed $value): bool => null !== $value,
        ));

        return $builder->withModifier(
            static fn (Customer $customer, self $builder) => $customer->defineShippingAddress($builder['shippingAddress'], $builder['shippingAddressDefinedAt']),
        );
    }

    public function billingAddressDefined(?PostalAddress $billingAddress = null, ?\DateTimeImmutable $definedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter(
            ['billingAddress' => $billingAddress, 'billingAddressDefinedAt' => $definedAt],
            static fn (mixed $value): bool => null !== $value,
        ));

        return $builder->withModifier(
            static fn (Customer $customer, self $builder) => $customer->defineBillingAddress($builder['billingAddress'], $builder['billingAddressDefinedAt']),
        );
    }

    public function erasureRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        $builder = null !== $requestedAt ? $this->withAttributes(requestedAt: $requestedAt) : $this;

        return $builder->withModifier(
            static fn (Customer $customer, self $builder) => $customer->requestErasure($builder['requestedAt']),
        );
    }

    public function erasureCancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $builder = null !== $cancelledAt ? $this->withAttributes(cancelledAt: $cancelledAt) : $this;

        return $builder->withModifier(
            static fn (Customer $customer, self $builder) => $customer->cancelErasure($builder['cancelledAt']),
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
        $now = Clock::get()->now();

        return [
            'id' => static fn (?self $builder): CustomerId => CustomerId::forIdentity(
                null !== $builder ? $builder['identityId'] : self::sample('identityId'),
            ),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'firstName' => static fn (): Name => Name::fromString(SeededFaker::get()->firstName()),
            'lastName' => static fn (): Name => Name::fromString(SeededFaker::get()->lastName()),
            'email' => static fn (): Email => Email::fromString(SeededFaker::get()->email()),
            'registeredAt' => static fn (): \DateTimeImmutable => $now,
            'shippingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'shippingAddressDefinedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'billingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'billingAddressDefinedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'requestedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'cancelledAt' => static fn (): \DateTimeImmutable => $now->modify('+2 days'),
            'erasedAt' => static fn (): \DateTimeImmutable => $now->modify('+2 day'),
        ];
    }

    protected function build(): Customer
    {
        return Customer::register(
            id: $this['id'],
            firstName: $this['firstName'],
            lastName: $this['lastName'],
            email: $this['email'],
            registeredAt: $this['registeredAt'],
        );
    }
}
