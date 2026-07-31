<?php

declare(strict_types=1);

namespace Api\Tests\OpenApi;

use PHPUnit\Framework\Attributes\Test;

final class ShipmentResourcePropertyDocumentationTest extends AbstractResourcePropertyDocumentationTestCase
{
    #[Test]
    public function itDocumentsTheShipmentProperties(): void
    {
        // When
        $described = self::describeSchemaProperties('Shipment');

        // Then
        self::assertSame([
            'id' => ['The identifier of the shipment.', '0193c5f5-1a44-7d18-b2c7-6f9e0a4d8b31'],
            'orderId' => ['The identifier of the order this shipment fulfils.', '0193c5f4-9c2e-7a1b-8f3d-2e5a7c9b1d40'],
            'customerId' => ['The identifier of the customer the shipment is addressed to.', '0193c5f4-7b10-7c42-9a6e-4d8f1b3c5e72'],
            'orderTotalInCents' => ['The total amount of the fulfilled order, in cents.', 3500],
            'status' => ['The current status of the shipment.', 'pending'],
            'createdAt' => ['The date and time when the shipment was created.', '2026-01-14T09:35:00+00:00'],
            'dispatchedAt' => ['The date and time when the shipment was handed to the carrier, if it was.', '2026-01-15T08:05:00+00:00'],
            'deliveredAt' => ['The date and time when the shipment was delivered, if it was.', '2026-01-17T11:40:00+00:00'],
            'orderCancelledAt' => ['The date and time when the fulfilled order was cancelled, if it was.', '2026-01-15T14:20:00+00:00'],
        ], $described);
    }
}
