<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\IntegrationEvent\ProductRepriced;

use Catalog\Listing\Application\IntegrationEvent\ProductRepriced\ProductRepricedIntegrationEvent;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ProductRepricedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $unitPriceInCents = ProductBuilder::sample('unitAmount')->cents;
        $builder = ProductBuilder::new()->withUnitPriceInCents($unitPriceInCents)->repriced($unitPriceInCents + 100);
        $product = $builder->create();

        // When
        $this->store($product);

        // Then
        $event = $this->publishedEventOf(ProductRepricedIntegrationEvent::class);
        self::assertSame($product->id->toString(), $event->productId);
        self::assertSame($builder['unitAmount']->cents, $event->unitPriceInCents);
        self::assertSame(
            $builder['repricedAt']->format(\DateTimeInterface::ATOM),
            $event->repricedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
