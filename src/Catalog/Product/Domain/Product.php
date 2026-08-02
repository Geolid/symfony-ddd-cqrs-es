<?php

declare(strict_types=1);

namespace Catalog\Product\Domain;

use Catalog\Product\Domain\Event\ProductListed;
use Catalog\Product\Domain\Event\ProductRepriced;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\ValueObject\Money;

#[Aggregate('catalog.product.product')]
final class Product implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private ProductId $id;
    private string $label;
    private Money $unitAmount;

    public function id(): ProductId
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function unitAmount(): Money
    {
        return $this->unitAmount;
    }

    public static function list(ProductId $id, string $label, Money $unitAmount, \DateTimeImmutable $listedAt): self
    {
        $self = new self();
        $self->recordThat(new ProductListed(
            id: $id->toString(),
            label: $label,
            unitAmountInCents: $unitAmount->toCents(),
            listedAt: $listedAt->format('c'),
        ));

        return $self;
    }

    public function reprice(Money $unitAmount, \DateTimeImmutable $repricedAt): void
    {
        $this->recordThat(new ProductRepriced(
            id: $this->id->toString(),
            unitAmountInCents: $unitAmount->toCents(),
            repricedAt: $repricedAt->format('c'),
        ));
    }

    #[Apply]
    private function applyProductListed(ProductListed $event): void
    {
        $this->id = ProductId::fromString($event->id);
        $this->label = $event->label;
        $this->unitAmount = Money::fromCents($event->unitAmountInCents);
    }

    #[Apply]
    private function applyProductRepriced(ProductRepriced $event): void
    {
        $this->unitAmount = Money::fromCents($event->unitAmountInCents);
    }
}
