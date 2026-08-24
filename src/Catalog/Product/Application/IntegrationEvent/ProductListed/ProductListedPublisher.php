<?php

declare(strict_types=1);

namespace Catalog\Product\Application\IntegrationEvent\ProductListed;

use Catalog\Product\Domain\Event\ProductListed;
use Catalog\Product\Domain\Product;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('catalog.product.product_listed_publisher')]
final readonly class ProductListedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ProductListed::class)]
    public function __invoke(ProductListed $event): void
    {
        $this->publisher->publish(Product::class, $event->id, new ProductListedIntegrationEvent(
            productId: $event->id,
            label: $event->label,
            unitAmountInCents: $event->unitAmountInCents,
            listedAt: $event->listedAt,
        ));
    }
}
