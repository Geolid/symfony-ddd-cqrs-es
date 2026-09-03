<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\IntegrationEvent\ProductRepriced;

use Catalog\Product\Application\IntegrationEvent\ProductRepriced\ProductRepricedIntegrationEvent;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ProductRepricedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ProductBuilder::new()->repriced();
        $product = $builder->create();

        // When
        $this->store($product);

        // Then
        $event = $this->publishedEventOf(ProductRepricedIntegrationEvent::class);
        self::assertSame($product->id->toString(), $event->productId);
        self::assertSame($builder['unitAmount']->cents, $event->unitAmountInCents);
        self::assertSame(
            $builder['repricedAt']->format(\DateTimeImmutable::ATOM),
            $event->repricedAt->format(\DateTimeImmutable::ATOM),
        );
    }
}
