<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\IntegrationEvent\ProductListed;

use Catalog\Listing\Domain\Event\ProductListed;
use Catalog\Listing\Domain\Product;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('catalog.listing.publish_product_listed')]
final readonly class ProductListedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ProductListed::class)]
    public function __invoke(ProductListed $event, Message $message): void
    {
        $this->publisher->publish(Product::class, $event->id->toString(), new ProductListedIntegrationEvent(
            productId: $event->id->toString(),
            label: $event->label->value,
            unitPriceInCents: $event->unitPrice->cents,
            listedAt: $event->listedAt,
        ), $message);
    }
}
