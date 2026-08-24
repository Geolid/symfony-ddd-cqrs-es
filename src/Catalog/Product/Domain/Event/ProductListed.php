<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('catalog.product.product.listed')]
final readonly class ProductListed implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $label,
        public int $unitAmountInCents,
        public string $listedAt,
    ) {
    }
}
