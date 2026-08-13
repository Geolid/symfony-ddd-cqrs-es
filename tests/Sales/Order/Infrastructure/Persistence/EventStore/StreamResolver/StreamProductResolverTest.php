<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\EventStore\StreamResolver;

use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Product\ProductResolverInterface;
use Support\AbstractIntegrationTestCase;

final class StreamProductResolverTest extends AbstractIntegrationTestCase
{
    private ProductResolverInterface $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->service(ProductResolverInterface::class);
    }

    #[Test]
    public function itResolvesAListedProduct(): void
    {
        // Given
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();

        // When
        $resolved = $this->resolver->resolveFor($product->id()->toString());

        // Then
        self::assertNotNull($resolved);
        self::assertSame($product->id()->toString(), $resolved->id);
        self::assertSame('Espresso cups, set of 6', $resolved->label);
        self::assertSame(1_750, $resolved->unitAmountInCents);
    }

    #[Test]
    public function itResolvesTheLatestPriceOfARepricedProduct(): void
    {
        // Given
        $product = ProductTestFactory::new()->withUnitAmountInCents(1_750)->repriced(2_000)->store();

        // When
        $resolved = $this->resolver->resolveFor($product->id()->toString());

        // Then
        self::assertNotNull($resolved);
        self::assertSame(2_000, $resolved->unitAmountInCents);
    }

    #[Test]
    public function itResolvesNothingForADelistedProduct(): void
    {
        // Given
        $product = ProductTestFactory::new()->delisted()->store();

        // When
        $resolved = $this->resolver->resolveFor($product->id()->toString());

        // Then
        self::assertNull($resolved);
    }

    #[Test]
    public function itResolvesNothingForAProductItNeverSaw(): void
    {
        // When
        $resolved = $this->resolver->resolveFor(Uuid::uuid7()->toString());

        // Then
        self::assertNull($resolved);
    }
}
