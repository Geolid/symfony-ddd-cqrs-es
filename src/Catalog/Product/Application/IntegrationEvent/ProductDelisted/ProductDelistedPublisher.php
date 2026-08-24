<?php

declare(strict_types=1);

namespace Catalog\Product\Application\IntegrationEvent\ProductDelisted;

use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\Product;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('catalog.product.product_delisted_publisher')]
final readonly class ProductDelistedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ProductDelisted::class)]
    public function __invoke(ProductDelisted $event): void
    {
        $this->publisher->publish(Product::class, $event->id, new ProductDelistedIntegrationEvent(
            productId: $event->id,
            delistedAt: $event->delistedAt,
        ));
    }
}
