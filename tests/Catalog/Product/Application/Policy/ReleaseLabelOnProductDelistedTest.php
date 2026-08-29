<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Policy;

use Catalog\Product\Application\Policy\ReleaseLabelOnProductDelisted;
use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\ValueObject\ProductUniqueKey;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

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
        $productId = Uuid::uuid7()->toString();
        $this->uniqueValues->reserve(UniqueKey::for(ProductUniqueKey::LABEL), 'Espresso cups, set of 6', $productId);

        // When
        ($this->policy)(new ProductDelisted($productId, new \DateTimeImmutable('2026-01-02T00:00:00+00:00')));

        // Then
        self::assertFalse($this->uniqueValues->exists(UniqueKey::for(ProductUniqueKey::LABEL), 'Espresso cups, set of 6'));
    }
}
