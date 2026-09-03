<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Policy;

use Catalog\Product\Application\Policy\ReleaseLabelOnProductDelisted;
use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\ValueObject\ProductUniqueKey;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReleaseLabelOnProductDelistedTest extends AbstractIntegrationTestCase
{
    private ReleaseLabelOnProductDelisted $policy;
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(ReleaseLabelOnProductDelisted::class);
        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itReleases(): void
    {
        // Given
        $productId = ProductBuilder::sample('id')->toString();
        $label = ProductBuilder::sample('label')->value;
        $labelKey = UniqueKey::for(ProductUniqueKey::LABEL);
        $this->uniqueValues->reserve($labelKey, $label, $productId);

        // When
        ($this->policy)(new ProductDelisted($productId, Clock::get()->now()));

        // Then
        self::assertFalse($this->uniqueValues->exists($labelKey, $label));
    }
}
