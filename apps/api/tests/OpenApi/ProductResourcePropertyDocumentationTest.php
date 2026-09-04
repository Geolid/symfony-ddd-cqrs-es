<?php

declare(strict_types=1);

namespace Api\Tests\OpenApi;

use PHPUnit\Framework\Attributes\Test;

final class ProductResourcePropertyDocumentationTest extends AbstractResourcePropertyDocumentationTestCase
{
    #[Test]
    public function itDocumentsTheProductProperties(): void
    {
        // When
        $described = self::describeSchemaProperties('Product');

        // Then
        self::assertSame([
            'id' => ['The identifier of the product.', '0193c5f4-6a2e-7d18-b2c7-6f9e0a4d8b31'],
            'label' => ['The label of the product.', 'Wireless mouse'],
            'unitPriceInCents' => ['The unit price of the product, in cents.', 2999],
        ], $described);
    }
}
