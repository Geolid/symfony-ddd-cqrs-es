<?php

declare(strict_types=1);

namespace Webhook\Tests\Controller;

use PHPUnit\Framework\Attributes\Test;
use Webhook\Tests\Support\AbstractWebhookTestCase;

final class CarrierWebhookControllerTest extends AbstractWebhookTestCase
{
    #[Test]
    public function itAcceptsAnUnknownEventType(): void
    {
        // Given
        $client = self::createClient();
        $body = json_encode(['event' => 'shipment.picked_up'], \JSON_THROW_ON_ERROR);

        // When
        $client->request('POST', '/webhooks/carrier', server: [
            'HTTP_X_CARRIER_SIGNATURE' => self::sign($body),
        ], content: $body);

        // Then
        self::assertResponseStatusCodeSame(202);
    }

    #[Test]
    public function itRejectsAnInvalidSignature(): void
    {
        // Given
        $client = self::createClient();
        $body = json_encode(['event' => 'shipment.delivered', 'shipmentId' => 'unknown'], \JSON_THROW_ON_ERROR);

        // When
        $client->request('POST', '/webhooks/carrier', server: [
            'HTTP_X_CARRIER_SIGNATURE' => 'invalid',
        ], content: $body);

        // Then
        self::assertResponseStatusCodeSame(401);
    }
}
