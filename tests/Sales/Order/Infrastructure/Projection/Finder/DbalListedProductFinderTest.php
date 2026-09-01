<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Finder;

use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Support\AbstractIntegrationTestCase;

final class DbalListedProductFinderTest extends AbstractIntegrationTestCase
{
    private ListedProductFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ListedProductFinderInterface::class);
    }

    #[Test]
    public function itFiltersByIds(): void
    {
        // Given
        $other = ProductBuilder::new()->withLabel('Untouched')->withUnitAmountInCents(500)->create();
        $cups = ProductBuilder::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($other, $cups);

        // When
        $results = iterator_to_array($this->finder->byIds($cups->id->toString(), Uuid::uuid7()->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cups->id->toString(), $results[0]->productId);
        self::assertSame('Espresso cups, set of 6', $results[0]->label);
        self::assertSame(1_750, $results[0]->unitAmountInCents);
    }
}
