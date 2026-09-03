<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Command\ListProductForSale;

use Catalog\Product\Application\Command\ListProductForSale\ListProductForSale;
use Catalog\Product\Application\Exception\ProductLabelAlreadyTakenException;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Domain\ValueObject\ProductUniqueKey;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class ListProductForSaleHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $id = ProductBuilder::sample('id')->toString();
        $label = ProductBuilder::sample('label')->value;
        $unitAmountInCents = ProductBuilder::sample('unitAmount')->cents;

        // When
        $this->dispatch(new ListProductForSale($id, $label, $unitAmountInCents));

        // Then
        $result = $this->service(ProductFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($label, $result->label);
        self::assertSame($unitAmountInCents, $result->unitAmountInCents);
    }

    #[Test]
    public function itFailsWhenLabelAlreadyTaken(): void
    {
        // Given
        $label = ProductBuilder::sample('label')->value;
        $this->service(UniqueValueRegistryInterface::class)->reserve(
            UniqueKey::for(ProductUniqueKey::LABEL),
            $label,
            ProductBuilder::sample('id')->toString(),
        );

        // Then
        $this->expectException(ProductLabelAlreadyTakenException::class);

        // When
        $this->dispatch(new ListProductForSale(
            ProductBuilder::sample('id')->toString(),
            $label,
            ProductBuilder::sample('unitAmount')->cents,
        ));
    }
}
