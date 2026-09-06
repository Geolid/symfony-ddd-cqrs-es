<?php

declare(strict_types=1);

namespace Finance\Payer\Domain;

use Finance\Payer\Domain\Event\PayerErased;
use Finance\Payer\Domain\Event\PayerPostalAddressDefined;
use Finance\Payer\Domain\Event\PayerRegistered;
use Finance\Payer\Domain\ValueObject\PayerId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('finance.payer.payer')]
final class Payer implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) PayerId $id;
    public private(set) ?PostalAddress $postalAddress = null;
    private bool $erased;

    public static function register(PayerId $id, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new PayerRegistered(
            id: $id->toString(),
            registeredAt: $registeredAt,
        ));

        return $self;
    }

    public function definePostalAddress(PostalAddress $postalAddress, \DateTimeImmutable $definedAt): void
    {
        if (true === $this->postalAddress?->equals($postalAddress)) {
            return;
        }

        $this->recordThat(new PayerPostalAddressDefined(
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

        $this->recordThat(new PayerErased(
            id: $this->id->toString(),
            erasedAt: $erasedAt,
        ));
    }

    #[Apply]
    private function applyRegistered(PayerRegistered $event): void
    {
        $this->id = PayerId::fromString($event->id);
        $this->erased = false;
    }

    #[Apply]
    private function applyPostalAddressDefined(PayerPostalAddressDefined $event): void
    {
        $this->postalAddress = $event->postalAddress;
    }

    #[Apply]
    private function applyErased(PayerErased $event): void
    {
        $this->erased = true;
    }
}
