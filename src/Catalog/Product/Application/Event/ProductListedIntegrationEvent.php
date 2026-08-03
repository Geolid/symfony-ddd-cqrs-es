<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\Event\IntegrationEventInterface;

#[Event('catalog.product.integration.listed')]
final readonly class ProductListedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $productId,
        public string $label,
        public int $unitAmountInCents,
        public string $listedAt,
    ) {
    }
}
