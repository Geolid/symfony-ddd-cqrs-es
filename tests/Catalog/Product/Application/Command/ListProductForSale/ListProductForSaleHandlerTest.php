<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Command\ListProductForSale;

use Catalog\Product\Application\Command\ListProductForSale\ListProductForSale;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Domain\ValueObject\ProductId;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ListProductForSaleHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsAProduct(): void
    {
        // Given
        $id = ProductId::generate()->toString();
        $command = new ListProductForSale($id, 'Espresso cups, set of 6', 1_750);

        // When
        $this->dispatch($command);

        // Then
        $result = $this->service(ProductFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame('Espresso cups, set of 6', $result->label);
        self::assertSame(1_750, $result->unitAmountInCents);
    }
}
