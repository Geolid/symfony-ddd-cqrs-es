<?php

declare(strict_types=1);

namespace Catalog\Product\Infrastructure\Persistence\EventStore\Repository;

use Catalog\Product\Domain\Product;
use Catalog\Product\Domain\Repository\ProductRepositoryInterface;
use Catalog\Product\Domain\ValueObject\ProductId;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Shared\Domain\Exception\AggregateNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @param Repository<Product> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.catalog.product.product.repository')]
        private Repository $repository,
    ) {
    }

    public function has(ProductId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(ProductId $id): Product
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw AggregateNotFoundException::forId(Product::class, $id->toString());
        }
    }

    public function save(Product $product): void
    {
        $this->repository->save($product);
    }
}
