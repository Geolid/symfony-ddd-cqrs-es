<?php

declare(strict_types=1);

namespace Catalog\Product\Infrastructure\Persistence\EventStore\Translator;

use Catalog\Product\Application\Event\ProductDelistedIntegrationEvent;
use Catalog\Product\Application\Event\ProductListedIntegrationEvent;
use Catalog\Product\Application\Event\ProductRepricedIntegrationEvent;
use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\Event\ProductListed;
use Catalog\Product\Domain\Event\ProductRepriced;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\EventStore\Translator\AbstractIntegrationEventTranslator;
use Shared\Infrastructure\Persistence\EventStore\Translator\Translator;

#[Translator('catalog.product.integration_translator')]
final readonly class ProductIntegrationEventTranslator extends AbstractIntegrationEventTranslator
{
    #[Subscribe(ProductListed::class)]
    public function onProductListed(ProductListed $event): void
    {
        $this->append(
            \sprintf('catalog.product.integration.%s', $event->id),
            new ProductListedIntegrationEvent(
                productId: $event->id,
                label: $event->label,
                unitAmountInCents: $event->unitAmountInCents,
                listedAt: $event->listedAt,
            ),
        );
    }

    #[Subscribe(ProductRepriced::class)]
    public function onProductRepriced(ProductRepriced $event): void
    {
        $this->append(
            \sprintf('catalog.product.integration.%s', $event->id),
            new ProductRepricedIntegrationEvent(
                productId: $event->id,
                unitAmountInCents: $event->unitAmountInCents,
                repricedAt: $event->repricedAt,
            ),
        );
    }

    #[Subscribe(ProductDelisted::class)]
    public function onProductDelisted(ProductDelisted $event): void
    {
        $this->append(
            \sprintf('catalog.product.integration.%s', $event->id),
            new ProductDelistedIntegrationEvent(
                productId: $event->id,
                delistedAt: $event->delistedAt,
            ),
        );
    }
}
