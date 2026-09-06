<?php

declare(strict_types=1);

namespace Catalog\Listing\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

#[Event('catalog.listing.product.listed')]
final readonly class ProductListed
{
    public function __construct(
        public string $id,
        public Label $label,
        public Money $unitPrice,
        public \DateTimeImmutable $listedAt,
    ) {
    }
}
