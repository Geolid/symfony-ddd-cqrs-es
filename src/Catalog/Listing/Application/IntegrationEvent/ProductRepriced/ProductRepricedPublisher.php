<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\IntegrationEvent\ProductRepriced;

use Catalog\Listing\Domain\Event\ProductRepriced;
use Catalog\Listing\Domain\Product;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('catalog.listing.publish_product_repriced')]
final readonly class ProductRepricedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ProductRepriced::class)]
    public function __invoke(ProductRepriced $event): void
    {
        $this->publisher->publish(Product::class, $event->id, new ProductRepricedIntegrationEvent(
            productId: $event->id,
            unitPriceInCents: $event->unitPriceInCents,
            repricedAt: $event->repricedAt,
        ));
    }
}
