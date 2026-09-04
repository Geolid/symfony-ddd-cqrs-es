<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\IntegrationEvent\ProductDelisted;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.catalog.listing.product.delisted')]
final readonly class ProductDelistedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $productId,
        public \DateTimeImmutable $delistedAt,
    ) {
    }
}
