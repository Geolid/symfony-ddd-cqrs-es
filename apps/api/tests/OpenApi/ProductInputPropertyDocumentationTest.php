<?php

declare(strict_types=1);

namespace Api\Tests\OpenApi;

use PHPUnit\Framework\Attributes\Test;

final class ProductInputPropertyDocumentationTest extends AbstractResourcePropertyDocumentationTestCase
{
    #[Test]
    public function itDocumentsTheListProductForSaleInputProperties(): void
    {
        // When
        $described = self::describeSchemaProperties('Product.ListProductForSaleInput');

        // Then
        self::assertSame([
            'label' => ['The label of the product.', 'Wireless mouse'],
            'unitAmountInCents' => ['The unit price of the product, in cents.', 2999],
        ], $described);
    }

    #[Test]
    public function itDocumentsTheRepriceProductInputProperties(): void
    {
        // When
        $described = self::describeSchemaProperties('Product.RepriceProductInput');

        // Then
        self::assertSame([
            'unitAmountInCents' => ['The new unit price of the product, in cents.', 3_499],
        ], $described);
    }
}
