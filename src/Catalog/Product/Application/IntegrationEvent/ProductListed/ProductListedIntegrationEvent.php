<?php

declare(strict_types=1);

namespace Catalog\Product\Application\IntegrationEvent\ProductListed;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.catalog.product.product.listed')]
final readonly class ProductListedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $productId,
        public string $label,
        public int $unitAmountInCents,
        public \DateTimeImmutable $listedAt,
    ) {
    }
}
