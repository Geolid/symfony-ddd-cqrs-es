<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Support\Builder;

use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Domain\Shopper;
use Shopping\Checkout\Domain\ValueObject\Email;
use Shopping\Checkout\Domain\ValueObject\ShopperId;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: ShopperId,
 *     identityId: string,
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
 * @extends AbstractAggregateBuilder<Shopper, Attributes>
 */
final class ShopperBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: ShopperId::fromString($id));
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(identityId: $identityId);
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
            static fn (Shopper $shopper, self $builder) => $shopper->defineShippingAddress($builder['shippingAddress'], $builder['shippingAddressDefinedAt']),
        );
    }

    public function billingAddressDefined(?PostalAddress $billingAddress = null, ?\DateTimeImmutable $definedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter(
            ['billingAddress' => $billingAddress, 'billingAddressDefinedAt' => $definedAt],
            static fn (mixed $value): bool => null !== $value,
        ));

        return $builder->withModifier(
            static fn (Shopper $shopper, self $builder) => $shopper->defineBillingAddress($builder['billingAddress'], $builder['billingAddressDefinedAt']),
        );
    }

    public function erasureRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        $builder = null !== $requestedAt ? $this->withAttributes(requestedAt: $requestedAt) : $this;

        return $builder->withModifier(
            static fn (Shopper $shopper, self $builder) => $shopper->requestErasure($builder['requestedAt']),
        );
    }

    public function erasureCancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $builder = null !== $cancelledAt ? $this->withAttributes(cancelledAt: $cancelledAt) : $this;

        return $builder->withModifier(
            static fn (Shopper $shopper, self $builder) => $shopper->cancelErasure($builder['cancelledAt']),
        );
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        $builder = null !== $erasedAt ? $this->withAttributes(erasedAt: $erasedAt) : $this;

        return $builder->withModifier(
            static fn (Shopper $shopper, self $builder) => $shopper->erase($builder['erasedAt']),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (?self $builder): ShopperId => ShopperId::forIdentity(
                null !== $builder ? $builder['identityId'] : self::sample('identityId'),
            ),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'email' => static fn (): Email => Email::fromString(SeededFaker::get()->unique()->safeEmail()),
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

    protected function build(): Shopper
    {
        return Shopper::register(
            id: $this['id'],
            identityId: $this['identityId'],
            email: $this['email'],
            registeredAt: $this['registeredAt'],
        );
    }
}
