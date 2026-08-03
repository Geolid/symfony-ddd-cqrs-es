<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Domain;

use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\Event\ProductListed;
use Catalog\Product\Domain\Event\ProductRepriced;
use Catalog\Product\Domain\Exception\ProductAlreadyDelistedException;
use Catalog\Product\Domain\Product;
use Catalog\Product\Domain\ValueObject\ProductId;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Money;

final class ProductTest extends AggregateRootTestCase
{
    #[Test]
    public function itListsAProduct(): void
    {
        $id = ProductId::generate();
        $listedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Product::list($id, 'Espresso cups, set of 6', Money::fromCents(1_750), $listedAt))
            ->then(new ProductListed($id->toString(), 'Espresso cups, set of 6', 1_750, $listedAt->format('c')));
    }

    #[Test]
    public function itRepricesAProduct(): void
    {
        $id = ProductId::generate()->toString();
        $listedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $repricedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new ProductListed($id, 'Espresso cups, set of 6', 1_750, $listedAt->format('c')))
            ->when(static fn (Product $product) => $product->reprice(Money::fromCents(1_950), $repricedAt))
            ->then(new ProductRepriced($id, 1_950, $repricedAt->format('c')));
    }

    #[Test]
    public function itDelistsAProduct(): void
    {
        $id = ProductId::generate()->toString();
        $listedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $delistedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new ProductListed($id, 'Espresso cups, set of 6', 1_750, $listedAt->format('c')))
            ->when(static fn (Product $product) => $product->delist($delistedAt))
            ->then(new ProductDelisted($id, $delistedAt->format('c')));
    }

    #[Test]
    public function itCannotDelistAnAlreadyDelistedProduct(): void
    {
        $id = ProductId::generate()->toString();
        $listedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $delistedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new ProductListed($id, 'Espresso cups, set of 6', 1_750, $listedAt->format('c')),
                new ProductDelisted($id, $delistedAt->format('c')),
            )
            ->when(static fn (Product $product) => $product->delist(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(ProductAlreadyDelistedException::class);
    }

    protected function aggregateClass(): string
    {
        return Product::class;
    }
}
