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
        $cups = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();
        $saucer = ProductTestFactory::new()->withLabel('Saucer')->withUnitAmountInCents(83)->store();
        ProductTestFactory::new()->withLabel('Untouched')->withUnitAmountInCents(500)->store();

        // When
        $results = iterator_to_array($this->finder->byIds($cups->id()->toString(), $saucer->id()->toString()));

        // Then
        self::assertCount(2, $results);
        $byId = [];
        foreach ($results as $result) {
            $byId[$result->productId] = $result;
        }
        self::assertSame('Espresso cups, set of 6', $byId[$cups->id()->toString()]->label);
        self::assertSame(1_750, $byId[$cups->id()->toString()]->unitAmountInCents);
        self::assertSame('Saucer', $byId[$saucer->id()->toString()]->label);
        self::assertSame(83, $byId[$saucer->id()->toString()]->unitAmountInCents);
    }

    #[Test]
    public function itFiltersOutUnknownIds(): void
    {
        // Given
        $cups = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();

        // When
        $results = iterator_to_array($this->finder->byIds($cups->id()->toString(), Uuid::uuid7()->toString()));

        // Then
        self::assertCount(1, $results);
    }

    #[Test]
    public function itFindsNothingWhenNoIdMatches(): void
    {
        // When
        $results = iterator_to_array($this->finder->byIds(Uuid::uuid7()->toString()));

        // Then
        self::assertCount(0, $results);
    }
}
