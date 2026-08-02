<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Command\ListProduct;

use Catalog\Product\Application\Command\ListProduct\ListProduct;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Domain\ProductId;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ListProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsAProduct(): void
    {
        // Given
        $id = ProductId::generate()->toString();
        $command = new ListProduct($id, 'Espresso cups, set of 6', 1_750);

        // When
        $this->dispatch($command);

        // Then
        $results = array_values(iterator_to_array($this->service(ProductFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame($id, $results[0]->id);
        self::assertSame('Espresso cups, set of 6', $results[0]->label);
        self::assertSame(1_750, $results[0]->unitAmountInCents);
    }
}
