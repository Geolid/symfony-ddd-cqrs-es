<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\Event\IntegrationEventInterface;

#[Event('catalog.product.integration.repriced')]
final readonly class ProductRepricedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $productId,
        public int $unitAmountInCents,
        public string $repricedAt,
    ) {
    }
}
