<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Support\Builder;

use Finance\Payer\Domain\Payer;
use Finance\Payer\Domain\ValueObject\PayerId;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: PayerId,
 *     registeredAt: \DateTimeImmutable,
 *     postalAddress: PostalAddress,
 *     postalAddressDefinedAt: \DateTimeImmutable,
 *     erasedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Payer, Attributes>
 */
final class PayerBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: PayerId::fromString($id));
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
            static fn (Payer $payer, self $builder) => $payer->definePostalAddress($builder['postalAddress'], $builder['postalAddressDefinedAt']),
        );
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        $builder = null !== $erasedAt ? $this->withAttributes(erasedAt: $erasedAt) : $this;

        return $builder->withModifier(
            static fn (Payer $payer, self $builder) => $payer->erase($builder['erasedAt']),
        );
    }

    protected static function defaults(): array
    {
        return [
            'id' => PayerId::generate(...),
            'registeredAt' => static fn (): \DateTimeImmutable => Clock::get()->now(),
            'postalAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'postalAddressDefinedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'erasedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+2 day'),
        ];
    }

    protected function build(): Payer
    {
        return Payer::register(
            id: $this['id'],
            registeredAt: $this['registeredAt'],
        );
    }
}
