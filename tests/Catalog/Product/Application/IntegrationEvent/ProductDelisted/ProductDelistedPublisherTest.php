<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\IntegrationEvent\ProductDelisted;

use Catalog\Product\Application\IntegrationEvent\ProductDelisted\ProductDelistedIntegrationEvent;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ProductDelistedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
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
