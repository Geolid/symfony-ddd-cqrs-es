<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\Command\PublishProduct;

use Catalog\Listing\Application\Command\PublishProduct\Exception\ProductLabelAlreadyTakenException;
use Catalog\Listing\Application\Command\PublishProduct\PublishProduct;
use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Application\Uniqueness\ProductUniqueKey;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class PublishProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $label = ProductBuilder::sample('label')->value;
        $unitPriceInCents = ProductBuilder::sample('unitPrice')->cents;

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
            Uuid::uuid7()->toString(),
        );

        // Then
        $this->expectException(ProductLabelAlreadyTakenException::class);

        // When
        $this->dispatch(new PublishProduct(
            Uuid::uuid7()->toString(),
            $label,
            ProductBuilder::sample('unitPrice')->cents,
        ));
    }
}
