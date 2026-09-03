<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Finder;

use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $other = ProductBuilder::new()->create();
        $label = ProductBuilder::sample('label');
        $unitAmount = ProductBuilder::sample('unitAmount');
        $cups = ProductBuilder::new()->withLabel($label->value)->withUnitAmountInCents($unitAmount->cents)->create();
        $this->store($other, $cups);

        // When
        $results = iterator_to_array($this->finder->byIds($cups->id->toString(), Uuid::uuid7()->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cups->id->toString(), $results[0]->productId);
        self::assertSame($label->value, $results[0]->label);
        self::assertSame($unitAmount->cents, $results[0]->unitAmountInCents);
    }
}
