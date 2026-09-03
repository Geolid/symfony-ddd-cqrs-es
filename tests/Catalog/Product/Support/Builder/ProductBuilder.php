<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Support\Builder;

use Catalog\Product\Domain\Product;
use Catalog\Product\Domain\ValueObject\ProductId;
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
 *     unitAmount: Money,
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

    public function withUnitAmountInCents(int $unitAmountInCents): self
    {
        return $this->withAttributes(unitAmount: Money::fromCents($unitAmountInCents));
    }

    public function withListedAt(\DateTimeImmutable $listedAt): self
    {
        return $this->withAttributes(listedAt: $listedAt);
    }

    public function repriced(?int $unitAmountInCents = null, ?\DateTimeImmutable $repricedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'unitAmount' => null !== $unitAmountInCents ? Money::fromCents($unitAmountInCents) : null,
            'repricedAt' => $repricedAt,
        ]));

        return $builder->withModifier(
            static fn (Product $product, self $builder) => $product->reprice($builder['unitAmount'], $builder['repricedAt']),
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
            'unitAmount' => static fn (): Money => Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000)),
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
            unitAmount: $this['unitAmount'],
            listedAt: $this['listedAt'],
        );
    }
}
