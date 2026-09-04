<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\Command\PublishProduct;

use Catalog\Listing\Application\Command\PublishProduct\PublishProduct;
use Catalog\Listing\Application\Exception\ProductLabelAlreadyTakenException;
use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Catalog\Listing\Domain\ValueObject\ProductUniqueKey;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class PublishProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $id = ProductId::generate()->toString();
        $label = ProductBuilder::sample('label')->value;
        $unitPriceInCents = ProductBuilder::sample('unitAmount')->cents;

        // When
        $this->dispatch(new PublishProduct($id, $label, $unitPriceInCents));

        // Then
        $result = $this->service(ProductFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($label, $result->label);
        self::assertSame($unitPriceInCents, $result->unitPriceInCents);
    }

    #[Test]
    public function itFailsWhenLabelAlreadyTaken(): void
    {
        // Given
        $label = ProductBuilder::sample('label')->value;
        $this->service(UniqueValueRegistryInterface::class)->reserve(
            UniqueKey::for(ProductUniqueKey::LABEL),
            $label,
            ProductId::generate()->toString(),
        );

        // Then
        $this->expectException(ProductLabelAlreadyTakenException::class);

        // When
        $this->dispatch(new PublishProduct(
            ProductId::generate()->toString(),
            $label,
            ProductBuilder::sample('unitAmount')->cents,
        ));
    }
}
