<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Finder;

use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
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
        $cups = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $other = ProductTestFactory::new()->withLabel('Untouched')->withUnitAmountInCents(500)->create();
        $this->store($cups, $other);

        // When
        $results = iterator_to_array($this->finder->byIds($cups->id->toString(), Uuid::uuid7()->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cups->id->toString(), $results[0]->productId);
        self::assertSame('Espresso cups, set of 6', $results[0]->label);
        self::assertSame(1_750, $results[0]->unitAmountInCents);
    }
}
