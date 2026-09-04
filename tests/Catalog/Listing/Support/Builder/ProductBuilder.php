<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Support\Builder;

use Catalog\Listing\Domain\Product;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: ProductId,
 *     label: Label,
 *     unitPrice: Money,
 *     listedAt: \DateTimeImmutable,
 *     repricedAt: \DateTimeImmutable,
 *     delistedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Product, Attributes>
 */
final class ProductBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: ProductId::fromString($id));
    }

    public function withLabel(string $label): self
    {
        return $this->withAttributes(label: Label::fromString($label));
    }

    public function withUnitPriceInCents(int $unitPriceInCents): self
    {
        return $this->withAttributes(unitPrice: Money::fromCents($unitPriceInCents));
    }

    public function withListedAt(\DateTimeImmutable $listedAt): self
    {
        return $this->withAttributes(listedAt: $listedAt);
    }

    public function repriced(?int $unitPriceInCents = null, ?\DateTimeImmutable $repricedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'unitPrice' => null !== $unitPriceInCents ? Money::fromCents($unitPriceInCents) : null,
            'repricedAt' => $repricedAt,
        ]));

        return $builder->withModifier(
            static fn (Product $product, self $builder) => $product->reprice($builder['unitPrice'], $builder['repricedAt']),
        );
    }

    public function delisted(?\DateTimeImmutable $delistedAt = null): self
    {
        $builder = null !== $delistedAt ? $this->withAttributes(delistedAt: $delistedAt) : $this;

        return $builder->withModifier(
            static fn (Product $product, self $builder) => $product->delist($builder['delistedAt']),
        );
    }

    protected static function defaults(): array
    {
        return [
            'id' => ProductId::generate(...),
            'label' => static function (): Label {
                Assert::string($label = SeededFaker::get()->unique()->words(3, true));

                return Label::fromString($label);
            },
            'unitPrice' => static fn (): Money => Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000)),
            'listedAt' => static fn (): \DateTimeImmutable => Clock::get()->now(),
            'repricedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'delistedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+2 day'),
        ];
    }

    protected function build(): Product
    {
        return Product::list(
            id: $this['id'],
            label: $this['label'],
            unitPrice: $this['unitPrice'],
            listedAt: $this['listedAt'],
        );
    }
}
