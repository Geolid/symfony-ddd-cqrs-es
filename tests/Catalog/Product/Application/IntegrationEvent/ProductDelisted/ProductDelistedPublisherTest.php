<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\IntegrationEvent\ProductDelisted;

use Catalog\Product\Application\IntegrationEvent\ProductDelisted\ProductDelistedIntegrationEvent;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ProductDelistedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $delistedAt = Clock::get()->now();
        $product = ProductTestFactory::new()->delisted($delistedAt)->create();

        // When
        $this->store($product);

        // Then
        $event = $this->publishedEventOf(ProductDelistedIntegrationEvent::class);
        self::assertSame($product->id->toString(), $event->productId);
        self::assertSame($delistedAt->format(\DateTimeImmutable::ATOM), $event->delistedAt->format(\DateTimeImmutable::ATOM));
    }
}
