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
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

final class ProductTest extends AggregateRootTestCase
{
    #[Test]
    public function itLists(): void
    {
        $id = ProductId::generate();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn (): Product => Product::list($id, Label::fromString('Espresso cups, set of 6'), Money::fromCents(1_750), $now))
            ->then(new ProductListed($id->toString(), 'Espresso cups, set of 6', 1_750, $now));
    }

    #[Test]
    public function itReprices(): void
    {
        $id = ProductId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $repricedAt = $now->modify('+1 day');

        $this
            ->given(new ProductListed($id, 'Espresso cups, set of 6', 1_750, $now))
            ->when(static fn (Product $product) => $product->reprice(Money::fromCents(1_950), $repricedAt))
            ->then(new ProductRepriced($id, 1_950, $repricedAt));
    }

    #[Test]
    public function itCannotRepriceWhenDelisted(): void
    {
        $id = ProductId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(
                new ProductListed($id, 'Espresso cups, set of 6', 1_750, $now),
                new ProductDelisted($id, $now->modify('+1 day')),
            )
            ->when(static fn (Product $product) => $product->reprice(Money::fromCents(1_950), $now->modify('+2 days')))
            ->expectsException(ProductAlreadyDelistedException::class);
    }

    #[Test]
    public function itDelists(): void
    {
        $id = ProductId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $delistedAt = $now->modify('+1 day');

        $this
            ->given(new ProductListed($id, 'Espresso cups, set of 6', 1_750, $now))
            ->when(static fn (Product $product) => $product->delist($delistedAt))
            ->then(new ProductDelisted($id, $delistedAt));
    }

    #[Test]
    public function itDoesNotDelistWhenAlreadyDelisted(): void
    {
        $id = ProductId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(
                new ProductListed($id, 'Espresso cups, set of 6', 1_750, $now),
                new ProductDelisted($id, $now->modify('+1 day')),
            )
            ->when(static fn (Product $product) => $product->delist($now->modify('+2 days')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Product::class;
    }
}
