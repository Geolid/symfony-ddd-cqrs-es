<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Support\Factory;

use Catalog\Listing\Domain\Product;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Tests\Support\Factory\LabelFactory;
use Shared\Tests\Support\Factory\MoneyFactory;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Inputs = array{
 *     id: ProductId,
 *     label: Label,
 *     unitPrice: Money,
 *     listedAt: \DateTimeImmutable,
 *     repricedAt: \DateTimeImmutable,
 *     delistedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<Product, Inputs>
 */
final class ProductFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return Product::class;
    }

    public function withId(string $id): self
    {
        return $this->with(['id' => ProductId::fromString($id)]);
    }

    public function withLabel(string $label): self
    {
        return $this->with(['label' => Label::fromString($label)]);
    }

    public function withUnitPriceInCents(int $unitPriceInCents): self
    {
        return $this->with(['unitPrice' => MoneyFactory::new(['cents' => $unitPriceInCents])->create()]);
    }

    public function withListedAt(\DateTimeImmutable $listedAt): self
    {
        return $this->with(['listedAt' => $listedAt]);
    }

    public function repriced(?int $unitPriceInCents = null, ?\DateTimeImmutable $repricedAt = null): self
    {
        return $this->with(array_filter([
            'unitPrice' => null !== $unitPriceInCents ? MoneyFactory::new(['cents' => $unitPriceInCents])->create() : null,
            'repricedAt' => $repricedAt,
        ]))->transition(
            static function (Product $product, array $inputs): void {
                $product->reprice($inputs['unitPrice'], $inputs['repricedAt']);
            },
        );
    }

    public function delisted(?\DateTimeImmutable $delistedAt = null): self
    {
        return $this->with(array_filter(['delistedAt' => $delistedAt]))->transition(
            static function (Product $product, array $inputs): void {
                $product->delist($inputs['delistedAt']);
            },
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return Product::list(
            id: $parameters['id'],
            label: $parameters['label'],
            unitPrice: $parameters['unitPrice'],
            listedAt: $parameters['listedAt'],
        );
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => ProductIdFactory::new(),
            'label' => LabelFactory::new(),
            'unitPrice' => MoneyFactory::new(),
            'listedAt' => $now,
            'repricedAt' => $now->modify('+1 day'),
            'delistedAt' => $now->modify('+2 day'),
        ];
    }
}
