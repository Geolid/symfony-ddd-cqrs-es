<?php

declare(strict_types=1);

namespace Catalog\Product\Application\IntegrationEvent\ProductRepriced;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.catalog.product.product.repriced')]
final readonly class ProductRepricedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $productId,
        public int $unitAmountInCents,
        public string $repricedAt,
    ) {
    }
}
