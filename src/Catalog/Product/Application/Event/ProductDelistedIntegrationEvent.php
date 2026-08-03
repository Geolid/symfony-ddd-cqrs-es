<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\Event\IntegrationEventInterface;

#[Event('catalog.product.integration.delisted')]
final readonly class ProductDelistedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $productId,
        public string $delistedAt,
    ) {
    }
}
