<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Support\Builder;

use Finance\Payer\Domain\Payer;
use Finance\Payer\Domain\ValueObject\PayerId;
use Shared\Domain\ValueObject\PostalAddress;
use Support\Builder\AbstractAggregateBuilder;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: PayerId,
 *     registeredAt: \DateTimeImmutable,
 *     addressRegisteredAt: \DateTimeImmutable,
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

    public function addressRegistered(PostalAddress $address, ?\DateTimeImmutable $registeredAt = null): self
    {
        $builder = null !== $registeredAt ? $this->withAttributes(addressRegisteredAt: $registeredAt) : $this;

        return $builder->withModifier(
            static fn (Payer $payer, self $builder) => $payer->registerAddress($address, $builder['addressRegisteredAt']),
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
            'addressRegisteredAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
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
