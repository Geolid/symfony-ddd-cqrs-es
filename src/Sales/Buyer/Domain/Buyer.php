<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Buyer\Domain\Event\BuyerBillingAddressDefined;
use Sales\Buyer\Domain\Event\BuyerErased;
use Sales\Buyer\Domain\Event\BuyerRegistered;
use Sales\Buyer\Domain\Event\BuyerShippingAddressDefined;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\Email;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('sales.buyer.buyer')]
final class Buyer implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) BuyerId $id;
    public private(set) string $identityId;
    public private(set) Email $email;
    public private(set) ?PostalAddress $shippingAddress = null;
    public private(set) ?PostalAddress $billingAddress = null;
    private bool $erased;

    public static function register(BuyerId $id, string $identityId, Email $email, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new BuyerRegistered(
            id: $id->toString(),
            identityId: $identityId,
            email: $email,
            registeredAt: $registeredAt,
        ));

        return $self;
    }

    public function defineShippingAddress(PostalAddress $shippingAddress, \DateTimeImmutable $definedAt): void
    {
        if (true === $this->shippingAddress?->equals($shippingAddress)) {
            return;
        }

        $this->recordThat(new BuyerShippingAddressDefined(
            id: $this->id->toString(),
            identityId: $this->identityId,
            postalAddress: $shippingAddress,
            definedAt: $definedAt,
        ));
    }

    public function defineBillingAddress(PostalAddress $billingAddress, \DateTimeImmutable $definedAt): void
    {
        if (true === $this->billingAddress?->equals($billingAddress)) {
            return;
        }

        $this->recordThat(new BuyerBillingAddressDefined(
            id: $this->id->toString(),
            identityId: $this->identityId,
            postalAddress: $billingAddress,
            definedAt: $definedAt,
        ));
    }

    public function erase(\DateTimeImmutable $erasedAt): void
    {
        if ($this->erased) {
            return;
        }

        $this->recordThat(new BuyerErased(
            id: $this->id->toString(),
            erasedAt: $erasedAt,
        ));
    }

    #[Apply]
    private function applyRegistered(BuyerRegistered $event): void
    {
        $this->id = BuyerId::fromString($event->id);
        $this->identityId = $event->identityId;
        $this->email = $event->email;
        $this->erased = false;
    }

    #[Apply]
    private function applyShippingAddressDefined(BuyerShippingAddressDefined $event): void
    {
        $this->shippingAddress = $event->postalAddress;
    }

    #[Apply]
    private function applyBillingAddressDefined(BuyerBillingAddressDefined $event): void
    {
        $this->billingAddress = $event->postalAddress;
    }

    #[Apply]
    private function applyErased(BuyerErased $event): void
    {
        $this->erased = true;
    }
}
