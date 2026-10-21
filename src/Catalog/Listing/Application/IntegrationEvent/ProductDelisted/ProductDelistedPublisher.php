<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\IntegrationEvent\ProductDelisted;

use Catalog\Listing\Domain\Event\ProductDelisted;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('catalog.listing.publish_product_delisted')]
final readonly class ProductDelistedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ProductDelisted::class)]
    public function __invoke(ProductDelisted $event, Message $message): void
    {
        $this->publisher->publish(new ProductDelistedIntegrationEvent(
            productId: $event->id->toString(),
            delistedAt: $event->delistedAt,
        ), $message);
    }
}
