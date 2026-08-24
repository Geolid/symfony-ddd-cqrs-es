<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('catalog.product.product.repriced')]
final readonly class ProductRepriced implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public int $unitAmountInCents,
        public string $repricedAt,
    ) {
    }
}
