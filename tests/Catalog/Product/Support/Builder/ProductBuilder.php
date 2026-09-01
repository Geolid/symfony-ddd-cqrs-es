<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Support\Builder;

use Catalog\Product\Domain\Product;
use Catalog\Product\Domain\ValueObject\ProductId;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Support\ClockSequence;
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
 * }
 *
 * @extends AbstractAggregateBuilder<Product, Attributes>
 */
final class ProductBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(['id' => ProductId::fromString($id)]);
    }

    public function withLabel(string $label): self
    {
        return $this->withAttributes(['label' => Label::fromString($label)]);
    }

    public function withUnitAmountInCents(int $unitAmountInCents): self
    {
        return $this->withAttributes(['unitAmount' => Money::fromCents($unitAmountInCents)]);
    }

    public function withListedAt(\DateTimeImmutable $listedAt): self
    {
        return $this->withAttributes(['listedAt' => $listedAt]);
    }

    public function repriced(int $unitAmountInCents, ?\DateTimeImmutable $repricedAt = null): self
    {
        $repricedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (Product $product) => $product->reprice(
            Money::fromCents($unitAmountInCents),
            $repricedAt,
        ));
    }

    public function delisted(?\DateTimeImmutable $delistedAt = null): self
    {
        $delistedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (Product $product) => $product->delist($delistedAt));
    }

    protected function defaults(): array
    {
        return [
            'id' => ProductId::generate(...),
            'label' => static function (): Label {
                Assert::string($label = SeededFaker::get()->words(3, true));

                return Label::fromString($label);
            },
            'unitAmount' => static fn (): Money => Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000)),
            'listedAt' => ClockSequence::next(...),
        ];
    }

    protected function build(): Product
    {
        return Product::list(
            id: $this->attribute('id'),
            label: $this->attribute('label'),
            unitAmount: $this->attribute('unitAmount'),
            listedAt: $this->attribute('listedAt'),
        );
    }
}
