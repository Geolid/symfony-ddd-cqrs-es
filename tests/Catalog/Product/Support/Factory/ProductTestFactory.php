<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Support\Factory;

use Catalog\Product\Domain\Product;
use Catalog\Product\Domain\ProductId;
use Shared\Domain\ValueObject\Money;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<Product>
 */
final class ProductTestFactory extends AbstractAggregateTestFactory
{
    public function withLabel(string $label): self
    {
        return static::new(array_merge($this->attributes, ['label' => $label]));
    }

    public function withUnitAmountInCents(int $unitAmountInCents): self
    {
        return static::new(array_merge($this->attributes, ['unitAmountInCents' => $unitAmountInCents]));
    }

    public function repriced(int $unitAmountInCents): self
    {
        return $this->withModifier(static fn (Product $product) => $product->reprice(
            Money::fromCents($unitAmountInCents),
            new \DateTimeImmutable('now +00:00'),
        ));
    }

    protected function defaults(): array
    {
        return [
            'id' => ProductId::generate()->toString(),
            'label' => self::faker()->words(3, true),
            'unitAmountInCents' => self::faker()->numberBetween(500, 5_000),
            'listedAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): Product
    {
        Assert::stringNotEmpty($id = $attributes['id']);
        Assert::stringNotEmpty($label = $attributes['label']);
        Assert::integer($unitAmountInCents = $attributes['unitAmountInCents']);
        Assert::isInstanceOf($listedAt = $attributes['listedAt'], \DateTimeInterface::class);

        return Product::list(
            ProductId::fromString($id),
            $label,
            Money::fromCents($unitAmountInCents),
            \DateTimeImmutable::createFromInterface($listedAt),
        );
    }
}
