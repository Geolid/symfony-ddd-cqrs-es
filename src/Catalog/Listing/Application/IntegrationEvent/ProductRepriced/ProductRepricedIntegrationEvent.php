<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\IntegrationEvent\ProductRepriced;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.catalog.listing.product.repriced')]
final readonly class ProductRepricedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $productId,
        public int $unitPriceInCents,
        public \DateTimeImmutable $repricedAt,
    ) {
    }
}
