<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\IntegrationEvent\ProductListed;

use Catalog\Product\Application\IntegrationEvent\ProductListed\ProductListedIntegrationEvent;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ProductListedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $listedAt = Clock::get()->now();
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->withListedAt($listedAt)->create();

        // When
        $this->store($product);

        // Then
        $event = $this->publishedEventOf(ProductListedIntegrationEvent::class);
        self::assertSame($product->id->toString(), $event->productId);
        self::assertSame('Espresso cups, set of 6', $event->label);
        self::assertSame(1_750, $event->unitAmountInCents);
        self::assertSame($listedAt->format(\DateTimeImmutable::ATOM), $event->listedAt->format(\DateTimeImmutable::ATOM));
    }
}
