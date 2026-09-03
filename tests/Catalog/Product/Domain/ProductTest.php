<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Domain;

use Catalog\Product\Domain\Event\ProductDelisted;
use Catalog\Product\Domain\Event\ProductListed;
use Catalog\Product\Domain\Event\ProductRepriced;
use Catalog\Product\Domain\Exception\ProductAlreadyDelistedException;
use Catalog\Product\Domain\Product;
use Catalog\Product\Domain\ValueObject\ProductId;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

final class ProductTest extends AggregateRootTestCase
{
    private ProductId $id;
    private Label $label;
    private Money $unitAmount;
    private \DateTimeImmutable $listedAt;
    private \DateTimeImmutable $delistedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = ProductBuilder::sample('id');
        $this->label = ProductBuilder::sample('label');
        $this->unitAmount = ProductBuilder::sample('unitAmount');
        $this->listedAt = ProductBuilder::sample('listedAt');
        $this->delistedAt = ProductBuilder::sample('delistedAt');
    }

    #[Test]
    public function itLists(): void
    {
        $this
            ->given()
            ->when(fn (): Product => Product::list($this->id, $this->label, $this->unitAmount, $this->listedAt))
            ->then($this->listed());
    }

    #[Test]
    public function itReprices(): void
    {
        $repricedUnitAmount = ProductBuilder::sample('unitAmount');
        $repricedAt = ProductBuilder::sample('repricedAt');

        $this
            ->given($this->listed())
            ->when(static fn (Product $product) => $product->reprice($repricedUnitAmount, $repricedAt))
            ->then(new ProductRepriced($this->id->toString(), $repricedUnitAmount->cents, $repricedAt));
    }

    #[Test]
    public function itCannotRepriceWhenDelisted(): void
    {
        $this
            ->given(
                $this->listed(),
                $this->delisted(),
            )
            ->when(static fn (Product $product) => $product->reprice(ProductBuilder::sample('unitAmount'), ProductBuilder::sample('repricedAt')))
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
        return new ProductListed($this->id->toString(), $this->label->value, $this->unitAmount->cents, $this->listedAt);
    }

    private function delisted(): ProductDelisted
    {
        return new ProductDelisted($this->id->toString(), $this->delistedAt);
    }
}
