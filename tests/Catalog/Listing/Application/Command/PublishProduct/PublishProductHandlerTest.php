<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\Command\PublishProduct;

use Catalog\Listing\Application\Command\PublishProduct\Exception\ProductLabelAlreadyInUseException;
use Catalog\Listing\Application\Command\PublishProduct\PublishProduct;
use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Application\ListingUniqueKey;
use Catalog\Tests\Listing\Support\Factory\ProductIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shared\Tests\Support\Factory\LabelFactory;
use Shared\Tests\Support\Factory\MoneyFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class PublishProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $id = ProductIdFactory::new()->create()->toString();
        $label = LabelFactory::new()->create()->value;
        $unitPriceInCents = MoneyFactory::new()->create()->cents;

        // When
        $this->dispatch(new PublishProduct($id, $label, $unitPriceInCents, 'EUR'));

        // Then
        $result = $this->service(ProductFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($label, $result->label);
        self::assertSame($unitPriceInCents, $result->unitPriceInCents);
    }

    #[Test]
    public function itFailsWhenLabelAlreadyInUse(): void
    {
        // Given
        $label = LabelFactory::new()->create()->value;
        $this->service(UniquenessRegistryInterface::class)->claim(
            UniqueKey::for(ListingUniqueKey::LABEL),
            $label,
            Uuid::uuid7()->toString(),
        );

        // Then
        $this->expectException(ProductLabelAlreadyInUseException::class);

        // When
        $this->dispatch(new PublishProduct(
            ProductIdFactory::new()->create()->toString(),
            $label,
            MoneyFactory::new()->create()->cents,
            'EUR',
        ));
    }
}
