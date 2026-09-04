<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Support\Builder;

use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\Email;
use Shared\Domain\ValueObject\PostalAddress;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: BuyerId,
 *     email: Email,
 *     registeredAt: \DateTimeImmutable,
 *     shippingAddressRegisteredAt: \DateTimeImmutable,
 *     billingAddressRegisteredAt: \DateTimeImmutable,
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

    public function shippingAddressRegistered(PostalAddress $shippingAddress, ?\DateTimeImmutable $registeredAt = null): self
    {
        $builder = null !== $registeredAt ? $this->withAttributes(shippingAddressRegisteredAt: $registeredAt) : $this;

        return $builder->withModifier(
            static fn (Buyer $buyer, self $builder) => $buyer->registerShippingAddress($shippingAddress, $builder['shippingAddressRegisteredAt']),
        );
    }

    public function billingAddressRegistered(PostalAddress $billingAddress, ?\DateTimeImmutable $registeredAt = null): self
    {
        $builder = null !== $registeredAt ? $this->withAttributes(billingAddressRegisteredAt: $registeredAt) : $this;

        return $builder->withModifier(
            static fn (Buyer $buyer, self $builder) => $buyer->registerBillingAddress($billingAddress, $builder['billingAddressRegisteredAt']),
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
            'shippingAddressRegisteredAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'billingAddressRegisteredAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
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
