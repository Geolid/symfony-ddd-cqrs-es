<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Processor;

use Catalog\Product\Application\Processor\ReleaseLabelOnProductDelisted;
use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\ValueObject\ProductUniqueValue;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class ReleaseLabelOnProductDelistedTest extends AbstractIntegrationTestCase
{
    private ReleaseLabelOnProductDelisted $processor;
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(ReleaseLabelOnProductDelisted::class);
        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itReleasesTheLabelOnProductDelisted(): void
    {
        // Given
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->store();
        $this->uniqueValues->reserve(ProductUniqueValue::LABEL, $product->label()->toString());

        // When
        ($this->processor)(new ProductDelisted($product->id()->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        self::assertFalse($this->uniqueValues->exists(ProductUniqueValue::LABEL, $product->label()->toString()));
    }
}
