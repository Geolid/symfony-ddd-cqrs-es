<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\IntegrationEvent\ProductDelisted;

use Catalog\Listing\Application\IntegrationEvent\ProductDelisted\ProductDelistedIntegrationEvent;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
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
            $builder['delistedAt']->format(\DateTimeInterface::ATOM),
            $event->delistedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
