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
use Support\AbstractIntegrationTestCase;

final class ListProductForSaleHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $factory = ProductBuilder::new();
        $id = $factory->attribute('id')->toString();
        $label = $factory->attribute('label')->value;
        $price = $factory->attribute('unitAmount')->cents;

        // When
        $this->dispatch(new ListProductForSale($id, $label, $price));

        // Then
        $result = $this->service(ProductFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($label, $result->label);
        self::assertSame($price, $result->unitAmountInCents);
    }

    #[Test]
    public function itFailsWhenLabelAlreadyTaken(): void
    {
        // Given
        $factory = ProductBuilder::new();
        $id = $factory->attribute('id')->toString();
        $label = $factory->attribute('label')->value;
        $price = $factory->attribute('unitAmount')->cents;

        $existingId = ProductBuilder::new()->attribute('id')->toString();
        $this->reserveLabel($label, $existingId);

        // Then
        $this->expectException(ProductLabelAlreadyTakenException::class);

        // When
        $this->dispatch(new ListProductForSale($id, $label, $price));
    }

    private function reserveLabel(string $label, string $existingId): void
    {
        $this->service(UniqueValueRegistryInterface::class)->reserve(
            UniqueKey::for(ProductUniqueKey::LABEL),
            $label,
            $existingId,
        );
    }
}
