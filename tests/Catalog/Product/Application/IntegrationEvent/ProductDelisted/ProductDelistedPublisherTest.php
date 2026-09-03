<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\IntegrationEvent\ProductDelisted;

use Catalog\Product\Application\IntegrationEvent\ProductDelisted\ProductDelistedIntegrationEvent;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ProductDelistedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ProductBuilder::new()->delisted();
        $product = $builder->create();

        // When
        $this->store($product);

        // Then
        $event = $this->publishedEventOf(ProductDelistedIntegrationEvent::class);
        self::assertSame($product->id->toString(), $event->productId);
        self::assertSame(
            $builder['delistedAt']->format(\DateTimeImmutable::ATOM),
            $event->delistedAt->format(\DateTimeImmutable::ATOM),
        );
    }
}
