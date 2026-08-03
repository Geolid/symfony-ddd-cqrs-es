<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Infrastructure\Persistence\EventStore\Translator;

use Catalog\Product\Application\Event\ProductDelistedIntegrationEvent;
use Catalog\Product\Application\Event\ProductListedIntegrationEvent;
use Catalog\Product\Application\Event\ProductRepricedIntegrationEvent;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ProductIntegrationEventTranslatorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesTheListingOnProductListed(): void
    {
        // When
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($product);

        // Then
        $published = $this->publishedTo(\sprintf('catalog.product.integration.%s', $product->id()->toString()));
        self::assertCount(1, $published);
        $event = $published[0];
        self::assertInstanceOf(ProductListedIntegrationEvent::class, $event);
        self::assertSame($product->id()->toString(), $event->productId);
        self::assertSame('Espresso cups, set of 6', $event->label);
        self::assertSame(1_750, $event->unitAmountInCents);
    }

    #[Test]
    public function itPublishesTheRepriceOnProductRepriced(): void
    {
        // When
        $product = ProductTestFactory::new()->repriced(2_000)->create();
        $this->store($product);

        // Then
        $published = $this->publishedTo(\sprintf('catalog.product.integration.%s', $product->id()->toString()));
        self::assertCount(2, $published);
        $event = $published[1];
        self::assertInstanceOf(ProductRepricedIntegrationEvent::class, $event);
        self::assertSame($product->id()->toString(), $event->productId);
        self::assertSame(2_000, $event->unitAmountInCents);
    }

    #[Test]
    public function itPublishesTheDelistingOnProductDelisted(): void
    {
        // When
        $product = ProductTestFactory::new()->delisted()->create();
        $this->store($product);

        // Then
        $published = $this->publishedTo(\sprintf('catalog.product.integration.%s', $product->id()->toString()));
        self::assertCount(2, $published);
        $event = $published[1];
        self::assertInstanceOf(ProductDelistedIntegrationEvent::class, $event);
        self::assertSame($product->id()->toString(), $event->productId);
    }

    /**
     * @return list<object>
     */
    private function publishedTo(string $streamId): array
    {
        $published = [];

        foreach ($this->service(Store::class)->load(new Criteria(new StreamCriterion($streamId))) as $message) {
            $published[] = $message->event();
        }

        return $published;
    }
}
