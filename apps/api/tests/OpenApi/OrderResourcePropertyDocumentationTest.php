<?php

declare(strict_types=1);

namespace Api\Tests\OpenApi;

use PHPUnit\Framework\Attributes\Test;

final class OrderResourcePropertyDocumentationTest extends AbstractResourcePropertyDocumentationTestCase
{
    #[Test]
    public function itDocumentsTheOrderProperties(): void
    {
        // When
        $described = self::describeSchemaProperties('Order');

        // Then
        self::assertSame([
            'id' => ['The identifier of the order.', '0193c5f4-9c2e-7a1b-8f3d-2e5a7c9b1d40'],
            'customerId' => ['The identifier of the customer who placed the order.', '0193c5f4-7b10-7c42-9a6e-4d8f1b3c5e72'],
            'totalAmountInCents' => ['The total amount of the order, in cents.', 3500],
            'status' => ['The current status of the order.', 'placed'],
            'placedAt' => ['The date and time when the order was placed.', '2026-01-14T09:30:00+00:00'],
            'cancelledAt' => ['The date and time when the order was cancelled, if it was.', '2026-01-15T14:20:00+00:00'],
        ], $described);
    }
}
