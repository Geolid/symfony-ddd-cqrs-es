<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\Policy;

use Catalog\Listing\Application\Policy\ReleaseLabelOnProductDelisted;
use Catalog\Listing\Domain\Event\ProductDelisted;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Catalog\Listing\Domain\ValueObject\ProductUniqueKey;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReleaseLabelOnProductDelistedTest extends AbstractIntegrationTestCase
{
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itReleases(): void
    {
        // Given
        $productId = ProductId::generate()->toString();
        $label = ProductBuilder::sample('label')->value;
        $labelKey = UniqueKey::for(ProductUniqueKey::LABEL);
        $this->uniqueValues->reserve($labelKey, $label, $productId);

        $otherLabel = ProductBuilder::sample('label')->value;
        $this->uniqueValues->reserve($labelKey, $otherLabel, ProductId::generate()->toString());

        // When
        $this->trigger(ReleaseLabelOnProductDelisted::class, new ProductDelisted($productId, Clock::get()->now()));

        // Then
        self::assertFalse($this->uniqueValues->exists($labelKey, $label));
        self::assertTrue($this->uniqueValues->exists($labelKey, $otherLabel));
    }
}
