<?php

declare(strict_types=1);

namespace Catalog\Product\Application\IntegrationEvent\ProductRepriced;

use Catalog\Product\Domain\Event\ProductRepriced;
use Catalog\Product\Domain\Product;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('catalog.product.product_repriced_publisher')]
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
            unitAmountInCents: $event->unitAmountInCents,
            repricedAt: $event->repricedAt,
        ));
    }
}
