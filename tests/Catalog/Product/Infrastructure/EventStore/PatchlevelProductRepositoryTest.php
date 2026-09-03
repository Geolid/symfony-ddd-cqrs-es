<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Infrastructure\EventStore;

use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\Repository\ProductRepositoryInterface;
use Catalog\Product\Domain\ValueObject\ProductId;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelProductRepositoryTest extends AbstractIntegrationTestCase
{
    private ProductRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ProductRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $product = ProductBuilder::new()->create();

        // When
        $this->repository->save($product);
        $loaded = $this->repository->load($product->id);

        // Then
        self::assertSame($product->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(ProductNotFoundException::class);

        // When
        $this->repository->load(ProductId::generate());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $product = ProductBuilder::new()->create();
        $this->repository->save($product);

        // When
        $exists = $this->repository->has($product->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(ProductId::generate());

        // Then
        self::assertFalse($notExists);
    }
}
