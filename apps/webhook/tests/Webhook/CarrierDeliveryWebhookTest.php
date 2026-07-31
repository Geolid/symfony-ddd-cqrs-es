<?php

declare(strict_types=1);

namespace Webhook\Tests\Webhook;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Webhook\Tests\Support\AbstractWebhookTestCase;

final class CarrierDeliveryWebhookTest extends AbstractWebhookTestCase
{
    #[Test]
    public function itRejectsAMissingSignature(): void
    {
        // Given
        $client = self::createClient();
        $body = self::body(Uuid::uuid7()->toString());

        // When
        $client->request('POST', '/webhooks/carrier-delivery', server: self::jsonHeaders(), content: $body);

        // Then
        self::assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function itRejectsAnInvalidSignature(): void
    {
        // Given
        $client = self::createClient();
        $body = self::body(Uuid::uuid7()->toString());

        // When
        $client->request('POST', '/webhooks/carrier-delivery', server: self::jsonHeaders(['HTTP_X_CARRIER_SIGNATURE' => 'invalid']), content: $body);

        // Then
        self::assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function itFailsToAcceptAMalformedShipmentId(): void
    {
        // Given
        $client = self::createClient();
        $body = self::body('not-a-uuid');

        // When
        $client->request('POST', '/webhooks/carrier-delivery', server: self::jsonHeaders(['HTTP_X_CARRIER_SIGNATURE' => self::sign($body)]), content: $body);

        // Then
        self::assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function itFailsToAcceptAPayloadMissingTheShipmentId(): void
    {
        // Given
        $client = self::createClient();
        $body = json_encode(['unexpected' => 'field'], \JSON_THROW_ON_ERROR);

        // When
        $client->request('POST', '/webhooks/carrier-delivery', server: self::jsonHeaders(['HTTP_X_CARRIER_SIGNATURE' => self::sign($body)]), content: $body);

        // Then
        self::assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function itFailsToAcceptAnUnknownShipment(): void
    {
        // Given
        $client = self::createClient();
        $body = self::body(Uuid::uuid7()->toString());

        // When
        $client->request('POST', '/webhooks/carrier-delivery', server: self::jsonHeaders(['HTTP_X_CARRIER_SIGNATURE' => self::sign($body)]), content: $body);

        // Then
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @param array<string, string> $extra
     *
     * @return array<string, string>
     */
    private static function jsonHeaders(array $extra = []): array
    {
        return array_merge(['CONTENT_TYPE' => 'application/json'], $extra);
    }

    private static function body(string $shipmentId): string
    {
        return json_encode(['shipmentId' => $shipmentId], \JSON_THROW_ON_ERROR);
    }
}
