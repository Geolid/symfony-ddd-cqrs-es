<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Domain;

use Catalog\Listing\Domain\Event\ProductDelisted;
use Catalog\Listing\Domain\Event\ProductListed;
use Catalog\Listing\Domain\Event\ProductRepriced;
use Catalog\Listing\Domain\Exception\ProductAlreadyDelistedException;
use Catalog\Listing\Domain\Product;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

final class ProductTest extends AggregateRootTestCase
{
    private ProductId $id;
    private Label $label;
    private Money $unitPrice;
    private \DateTimeImmutable $listedAt;
    private \DateTimeImmutable $delistedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = ProductId::generate();
        $this->label = ProductBuilder::sample('label');
        $this->unitPrice = ProductBuilder::sample('unitPrice');
        $this->listedAt = ProductBuilder::sample('listedAt');
        $this->delistedAt = ProductBuilder::sample('delistedAt');
    }

    #[Test]
    public function itLists(): void
    {
        $this
            ->given()
            ->when(fn (): Product => Product::list($this->id, $this->label, $this->unitPrice, $this->listedAt))
            ->then($this->listed());
    }

    #[Test]
    public function itReprices(): void
    {
        $repricedUnitPrice = ProductBuilder::sample('unitPrice');
        $repricedAt = ProductBuilder::sample('repricedAt');

        $this
            ->given($this->listed())
            ->when(static fn (Product $product) => $product->reprice($repricedUnitPrice, $repricedAt))
            ->then(new ProductRepriced($this->id->toString(), $repricedUnitPrice->cents, $repricedAt));
    }

    #[Test]
    public function itCannotRepriceWhenDelisted(): void
    {
        $this
            ->given(
                $this->listed(),
                $this->delisted(),
            )
            ->when(static fn (Product $product) => $product->reprice(ProductBuilder::sample('unitPrice'), ProductBuilder::sample('repricedAt')))
            ->expectsException(ProductAlreadyDelistedException::class);
    }

    #[Test]
    public function itDelists(): void
    {
        $this
            ->given($this->listed())
            ->when(fn (Product $product) => $product->delist($this->delistedAt))
            ->then($this->delisted());
    }

    #[Test]
    public function itDoesNotDelistWhenAlreadyDelisted(): void
    {
        $this
            ->given(
                $this->listed(),
                $this->delisted(),
            )
            ->when(static fn (Product $product) => $product->delist(ProductBuilder::sample('delistedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Product::class;
    }

    private function listed(): ProductListed
    {
        return new ProductListed($this->id->toString(), $this->label->value, $this->unitPrice->cents, $this->listedAt);
    }

    private function delisted(): ProductDelisted
    {
        return new ProductDelisted($this->id->toString(), $this->delistedAt);
    }
}
