<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Infrastructure\Persistence\EventStore\Repository;

use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\ProductId;
use Catalog\Product\Domain\Repository\ProductRepositoryInterface;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ProductRepositoryTest extends AbstractIntegrationTestCase
{
    private ProductRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ProductRepositoryInterface::class);
    }

    #[Test]
    public function itLoadsAProductItSaved(): void
    {
        // Given
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->create();

        // When
        $this->repository->save($product);

        // Then
        $id = $product->id();
        self::assertTrue($this->repository->has($id));
        self::assertSame('Espresso cups, set of 6', $this->repository->load($id)->label());
    }

    #[Test]
    public function itThrowsOnAProductItNeverSaved(): void
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
