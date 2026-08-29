<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\IntegrationEvent\ProductRepriced;

use Catalog\Product\Application\IntegrationEvent\ProductRepriced\ProductRepricedIntegrationEvent;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ProductRepricedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $repricedAt = Clock::get()->now();
        $product = ProductTestFactory::new()->repriced(2_000, $repricedAt)->create();

        // When
        $this->store($product);

        // Then
        $event = $this->publishedEventOf(ProductRepricedIntegrationEvent::class);
        self::assertSame($product->id->toString(), $event->productId);
        self::assertSame(2_000, $event->unitAmountInCents);
        self::assertSame($repricedAt->format(\DateTimeImmutable::ATOM), $event->repricedAt->format(\DateTimeImmutable::ATOM));
    }
}
