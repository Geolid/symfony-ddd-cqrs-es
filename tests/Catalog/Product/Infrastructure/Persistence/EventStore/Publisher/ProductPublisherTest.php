<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Infrastructure\Persistence\EventStore\Publisher;

use Catalog\Product\Application\Event\ProductDelistedIntegrationEvent;
use Catalog\Product\Application\Event\ProductListedIntegrationEvent;
use Catalog\Product\Application\Event\ProductRepricedIntegrationEvent;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ProductPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesOnProductListed(): void
    {
        // Given
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();

        // When
        $this->store($product);

        // Then
        $event = $this->publishedEventOfType(ProductListedIntegrationEvent::class);
        self::assertSame($product->id->toString(), $event->productId);
        self::assertSame('Espresso cups, set of 6', $event->label);
        self::assertSame(1_750, $event->unitAmountInCents);
    }

    #[Test]
    public function itPublishesOnProductRepriced(): void
    {
        // Given
        $product = ProductTestFactory::new()->repriced(2_000)->create();

        // When
        $this->store($product);

        // Then
        $event = $this->publishedEventOfType(ProductRepricedIntegrationEvent::class);
        self::assertSame($product->id->toString(), $event->productId);
        self::assertSame(2_000, $event->unitAmountInCents);
    }

    #[Test]
    public function itPublishesOnProductDelisted(): void
    {
        // Given
        $product = ProductTestFactory::new()->delisted()->create();

        // When
        $this->store($product);

        // Then
        $event = $this->publishedEventOfType(ProductDelistedIntegrationEvent::class);
        self::assertSame($product->id->toString(), $event->productId);
    }
}
