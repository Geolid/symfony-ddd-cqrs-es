<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\IntegrationEvent\ProductListed;

use Catalog\Product\Application\IntegrationEvent\ProductListed\ProductListedIntegrationEvent;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ProductListedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ProductBuilder::new();
        $product = $builder->create();

        // When
        $this->store($product);

        // Then
        $event = $this->publishedEventOf(ProductListedIntegrationEvent::class);
        self::assertSame($product->id->toString(), $event->productId);
        self::assertSame($builder['label']->value, $event->label);
        self::assertSame($builder['unitAmount']->cents, $event->unitAmountInCents);
        self::assertSame(
            $builder['listedAt']->format(\DateTimeInterface::ATOM),
            $event->listedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
