<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Infrastructure\EventStore;

use Catalog\Listing\Domain\Exception\ProductAlreadyExistsException;
use Catalog\Listing\Domain\Exception\ProductNotFoundException;
use Catalog\Listing\Domain\Product;
use Catalog\Listing\Domain\Repository\ProductRepositoryInterface;
use Catalog\Tests\Listing\Support\Factory\ProductFactory;
use Catalog\Tests\Listing\Support\Factory\ProductIdFactory;
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
        $product = ProductFactory::new()
            ->repriced()
            ->delisted()
            ->create();

        // When
        $this->repository->save($product);
        $loaded = $this->repository->load($product->id);

        // Then
        self::assertSame($this->propertiesOf($product), $this->propertiesOf($loaded));
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $product = ProductFactory::new()
            ->create();
        $this->store($product);
        $duplicate = ProductFactory::new()
            ->withId($product->id->toString())
            ->create();

        // Then
        $this->expectException(ProductAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(ProductNotFoundException::class);

        // When
        $this->repository->load(ProductIdFactory::new()->create());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $product = ProductFactory::new()->create();
        $this->store($product);

        // When
        $exists = $this->repository->has($product->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(ProductIdFactory::new()->create());

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesOf(Product $product): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $product->id->toString(),
            'label' => $product->label->value,
            'unitPrice' => ['cents' => $product->unitPrice->cents, 'currency' => $product->unitPrice->currency->value],
            'listedAt' => $atom($product->listedAt),
            'repricedAt' => $atom($product->repricedAt),
            'delisted' => $product->delisted,
            'delistedAt' => $atom($product->delistedAt),
        ];
    }
}
