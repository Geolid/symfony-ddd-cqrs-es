<?php

declare(strict_types=1);

namespace Catalog\Product\Infrastructure\Persistence\EventStore\Publisher;

use Catalog\Product\Application\Event\ProductDelistedIntegrationEvent;
use Catalog\Product\Application\Event\ProductListedIntegrationEvent;
use Catalog\Product\Application\Event\ProductRepricedIntegrationEvent;
use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\Event\ProductListed;
use Catalog\Product\Domain\Event\ProductRepriced;
use Catalog\Product\Domain\Product;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\EventStore\Publisher\IntegrationEventAppenderInterface;
use Shared\Infrastructure\Persistence\EventStore\Publisher\Publisher;

#[Publisher('catalog.product.integration')]
final readonly class ProductPublisher
{
    public function __construct(private IntegrationEventAppenderInterface $appender)
    {
    }

    #[Subscribe(ProductListed::class)]
    public function onProductListed(ProductListed $event): void
    {
        $this->appender->append(Product::class, $event->id, new ProductListedIntegrationEvent(
            productId: $event->id,
            label: $event->label,
            unitAmountInCents: $event->unitAmountInCents,
            listedAt: $event->listedAt,
        ));
    }

    #[Subscribe(ProductRepriced::class)]
    public function onProductRepriced(ProductRepriced $event): void
    {
        $this->appender->append(Product::class, $event->id, new ProductRepricedIntegrationEvent(
            productId: $event->id,
            unitAmountInCents: $event->unitAmountInCents,
            repricedAt: $event->repricedAt,
        ));
    }

    #[Subscribe(ProductDelisted::class)]
    public function onProductDelisted(ProductDelisted $event): void
    {
        $this->appender->append(Product::class, $event->id, new ProductDelistedIntegrationEvent(
            productId: $event->id,
            delistedAt: $event->delistedAt,
        ));
    }
}
