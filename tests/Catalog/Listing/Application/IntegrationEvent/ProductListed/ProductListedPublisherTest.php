<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\IntegrationEvent\ProductListed;

use Catalog\Listing\Application\IntegrationEvent\ProductListed\ProductListedIntegrationEvent;
use Catalog\Tests\Listing\Support\Factory\ProductFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ProductListedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $product = ProductFactory::new()->create();

        // When
        $this->store($product);

        // Then
        $event = $this->publishedEventOf(ProductListedIntegrationEvent::class);
        self::assertSame($product->id->toString(), $event->productId);
        self::assertSame($product->label->value, $event->label);
        self::assertSame($product->unitPrice->cents, $event->unitPriceInCents);
        self::assertSame(
            $product->listedAt->format(\DateTimeInterface::ATOM),
            $event->listedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
