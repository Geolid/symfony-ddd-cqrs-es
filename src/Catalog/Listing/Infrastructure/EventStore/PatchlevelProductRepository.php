<?php

declare(strict_types=1);

namespace Catalog\Listing\Infrastructure\EventStore;

use Catalog\Listing\Domain\Exception\ProductAlreadyExistsException;
use Catalog\Listing\Domain\Exception\ProductNotFoundException;
use Catalog\Listing\Domain\Product;
use Catalog\Listing\Domain\Repository\ProductRepositoryInterface;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelProductRepository implements ProductRepositoryInterface
{
    /**
     * @param Repository<Product> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.catalog.listing.product.repository')]
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
            throw ProductNotFoundException::forId($id->toString());
        }
    }

    public function save(Product $product): void
    {
        try {
            $this->repository->save($product);
        } catch (AggregateAlreadyExists) {
            throw ProductAlreadyExistsException::forId($product->id->toString());
        }
    }
}
