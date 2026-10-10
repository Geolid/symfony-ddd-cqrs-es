<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\IntegrationEvent\ProductRepriced;

use Catalog\Listing\Application\IntegrationEvent\ProductRepriced\ProductRepricedIntegrationEvent;
use Catalog\Tests\Listing\Support\Factory\ProductFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Tests\Support\Factory\MoneyFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class ProductRepricedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $unitPriceInCents = MoneyFactory::new()->create()->cents;
        $product = ProductFactory::new()->withUnitPriceInCents($unitPriceInCents)->repriced($unitPriceInCents + 100)->create();

        // When
        $this->store($product);

        // Then
        $event = $this->publishedEventOf(ProductRepricedIntegrationEvent::class);
        self::assertSame($product->id->toString(), $event->productId);
        self::assertSame($product->unitPrice->cents, $event->unitPriceInCents);
        self::assertSameDate($product->repricedAt, $event->repricedAt);
    }
}
