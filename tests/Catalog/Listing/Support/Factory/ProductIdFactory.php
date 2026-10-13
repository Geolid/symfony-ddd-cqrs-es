<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Support\Factory;

use Catalog\Listing\Domain\ValueObject\ProductId;
use Ramsey\Uuid\Uuid;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<ProductId>
 */
final class ProductIdFactory extends ObjectFactory
{
    public static function class(): string
    {
        return ProductId::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('fromString'));
    }

    protected function defaults(): array
    {
        return ['id' => Uuid::uuid7()->toString()];
    }
}
