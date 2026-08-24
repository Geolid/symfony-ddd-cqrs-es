<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\IntegrationEvent\ProductListed;

use Catalog\Product\Application\IntegrationEvent\ProductListed\ProductListedIntegrationEvent;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ProductListedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
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
}
