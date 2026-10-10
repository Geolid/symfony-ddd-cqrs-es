<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Domain;

use Catalog\Listing\Domain\Event\ProductDelisted;
use Catalog\Listing\Domain\Event\ProductListed;
use Catalog\Listing\Domain\Event\ProductRepriced;
use Catalog\Listing\Domain\Exception\ProductAlreadyDelistedException;
use Catalog\Listing\Domain\Product;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Catalog\Tests\Listing\Support\Factory\ProductIdFactory;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Tests\Support\Factory\LabelFactory;
use Shared\Tests\Support\Factory\MoneyFactory;
use Symfony\Component\Clock\Clock;

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

        $this->id = ProductIdFactory::new()->create();
        $this->label = LabelFactory::new()->create();
        $this->unitPrice = MoneyFactory::new()->create();
        $this->listedAt = Clock::get()->now();
        $this->delistedAt = $this->listedAt->modify('+2 day');
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
        $repricedUnitPrice = MoneyFactory::new()->create();
        $repricedAt = Clock::get()->now()->modify('+1 day');

        $this
            ->given($this->listed())
            ->when(static fn (Product $product) => $product->reprice($repricedUnitPrice, $repricedAt))
            ->then(new ProductRepriced($this->id, $repricedUnitPrice, $repricedAt));
    }

    #[Test]
    public function itCannotRepriceWhenDelisted(): void
    {
        $this
            ->given(
                $this->listed(),
                $this->delisted(),
            )
            ->when(static fn (Product $product) => $product->reprice(MoneyFactory::new()->create(), Clock::get()->now()->modify('+1 day')))
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
            ->when(fn (Product $product) => $product->delist($this->delistedAt))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Product::class;
    }

    private function listed(): ProductListed
    {
        return new ProductListed($this->id, $this->label, $this->unitPrice, $this->listedAt);
    }

    private function delisted(): ProductDelisted
    {
        return new ProductDelisted($this->id, $this->delistedAt);
    }
}
