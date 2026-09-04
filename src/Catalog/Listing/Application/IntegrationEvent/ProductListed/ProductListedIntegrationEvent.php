<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\IntegrationEvent\ProductListed;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.catalog.listing.product.listed')]
final readonly class ProductListedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $productId,
        public string $label,
        public int $unitPriceInCents,
        public \DateTimeImmutable $listedAt,
    ) {
    }
}
