<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Infrastructure\EventStore;

use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\Repository\ProductRepositoryInterface;
use Catalog\Product\Domain\ValueObject\ProductId;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

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

        // Then
        $id = $product->id;
        self::assertTrue($this->repository->has($id));
        self::assertSame($id->toString(), $this->repository->load($id)->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Given
        $id = ProductId::generate();

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(ProductNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
