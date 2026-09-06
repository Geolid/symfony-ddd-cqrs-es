<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Sales\Buyer\Domain\Event\BuyerErased;
use Sales\Buyer\Domain\Event\BuyerPostalAddressDefined;
use Sales\Buyer\Domain\Event\BuyerRegistered;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\Email;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('sales.buyer.buyer')]
final class Buyer implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) BuyerId $id;
    public private(set) Email $email;
    public private(set) ?PostalAddress $postalAddress = null;
    private bool $erased;

    public static function register(BuyerId $id, Email $email, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new BuyerRegistered(
            id: $id->toString(),
            email: $email,
            registeredAt: $registeredAt,
        ));

        return $self;
    }

    public function definePostalAddress(PostalAddress $postalAddress, \DateTimeImmutable $definedAt): void
    {
        if (true === $this->postalAddress?->equals($postalAddress)) {
            return;
        }

        $this->recordThat(new BuyerPostalAddressDefined(
            id: $this->id->toString(),
            postalAddress: $postalAddress,
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
        $this->email = $event->email;
        $this->erased = false;
    }

    #[Apply]
    private function applyPostalAddressDefined(BuyerPostalAddressDefined $event): void
    {
        $this->postalAddress = $event->postalAddress;
    }

    #[Apply]
    private function applyErased(BuyerErased $event): void
    {
        $this->erased = true;
    }
}
