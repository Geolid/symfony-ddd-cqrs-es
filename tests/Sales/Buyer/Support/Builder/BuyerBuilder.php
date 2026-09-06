<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Support\Builder;

use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\Email;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: BuyerId,
 *     email: Email,
 *     registeredAt: \DateTimeImmutable,
 *     postalAddress: PostalAddress,
 *     postalAddressDefinedAt: \DateTimeImmutable,
 *     erasedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Buyer, Attributes>
 */
final class BuyerBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: BuyerId::fromString($id));
    }

    public function withEmail(string $email): self
    {
        return $this->withAttributes(email: Email::fromString($email));
    }

    public function withRegisteredAt(\DateTimeImmutable $registeredAt): self
    {
        return $this->withAttributes(registeredAt: $registeredAt);
    }

    public function postalAddressDefined(?PostalAddress $postalAddress = null, ?\DateTimeImmutable $definedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter(
            ['postalAddress' => $postalAddress, 'postalAddressDefinedAt' => $definedAt],
            static fn (mixed $value): bool => null !== $value,
        ));

        return $builder->withModifier(
            static fn (Buyer $buyer, self $builder) => $buyer->definePostalAddress($builder['postalAddress'], $builder['postalAddressDefinedAt']),
        );
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        $builder = null !== $erasedAt ? $this->withAttributes(erasedAt: $erasedAt) : $this;

        return $builder->withModifier(
            static fn (Buyer $buyer, self $builder) => $buyer->erase($builder['erasedAt']),
        );
    }

    protected static function defaults(): array
    {
        return [
            'id' => BuyerId::generate(...),
            'email' => static fn (): Email => Email::fromString(SeededFaker::get()->unique()->safeEmail()),
            'registeredAt' => static fn (): \DateTimeImmutable => Clock::get()->now(),
            'postalAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'postalAddressDefinedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'erasedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+2 day'),
        ];
    }

    protected function build(): Buyer
    {
        return Buyer::register(
            id: $this['id'],
            email: $this['email'],
            registeredAt: $this['registeredAt'],
        );
    }
}
