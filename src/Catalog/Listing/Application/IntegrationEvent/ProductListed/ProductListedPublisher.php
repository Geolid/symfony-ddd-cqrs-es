<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\IntegrationEvent\ProductListed;

use Catalog\Listing\Domain\Event\ProductListed;
use Catalog\Listing\Domain\Product;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('catalog.listing.publish_product_listed')]
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
            label: $event->label->value,
            unitPriceInCents: $event->unitPrice->cents,
            listedAt: $event->listedAt,
        ));
    }
}
