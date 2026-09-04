<?php

declare(strict_types=1);

namespace Catalog\Listing\Domain;

use Catalog\Listing\Domain\Event\ProductDelisted;
use Catalog\Listing\Domain\Event\ProductListed;
use Catalog\Listing\Domain\Event\ProductRepriced;
use Catalog\Listing\Domain\Exception\ProductAlreadyDelistedException;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

#[Aggregate('catalog.listing.product')]
final class Product implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) ProductId $id;
    private bool $delisted;

    public static function list(ProductId $id, Label $label, Money $unitAmount, \DateTimeImmutable $listedAt): self
    {
        $self = new self();
        $self->recordThat(new ProductListed(
            id: $id->toString(),
            label: $label->value,
            unitPriceInCents: $unitAmount->cents,
            listedAt: $listedAt,
        ));

        return $self;
    }

    /**
     * @throws ProductAlreadyDelistedException
     */
    public function reprice(Money $unitAmount, \DateTimeImmutable $repricedAt): void
    {
        if ($this->delisted) {
            throw ProductAlreadyDelistedException::forId($this->id);
        }

        $this->recordThat(new ProductRepriced(
            id: $this->id->toString(),
            unitPriceInCents: $unitAmount->cents,
            repricedAt: $repricedAt,
        ));
    }

    public function delist(\DateTimeImmutable $delistedAt): void
    {
        if ($this->delisted) {
            return;
        }

        $this->recordThat(new ProductDelisted(
            id: $this->id->toString(),
            delistedAt: $delistedAt,
        ));
    }

    #[Apply]
    private function applyListed(ProductListed $event): void
    {
        $this->id = ProductId::fromString($event->id);
        $this->delisted = false;
    }

    #[Apply]
    private function applyRepriced(ProductRepriced $event): void
    {
    }

    #[Apply]
    private function applyDelisted(ProductDelisted $event): void
    {
        $this->delisted = true;
    }
}
