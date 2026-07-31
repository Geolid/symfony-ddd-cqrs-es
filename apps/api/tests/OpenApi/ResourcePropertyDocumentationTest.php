<?php

declare(strict_types=1);

namespace Api\Tests\OpenApi;

use Api\Tests\Support\AbstractApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use PHPUnit\Framework\Attributes\Test;
use Webmozart\Assert\Assert;

final class ResourcePropertyDocumentationTest extends AbstractApiTestCase
{
    #[Test]
    public function itDocumentsTheOrderProperties(): void
    {
        // Given
        $client = self::openApiClient();

        // When
        $client->request('GET', '/docs');

        // Then
        self::assertSame([
            'id' => ['The identifier of the order.', '0193c5f4-9c2e-7a1b-8f3d-2e5a7c9b1d40'],
            'customerId' => ['The identifier of the customer who placed the order.', '0193c5f4-7b10-7c42-9a6e-4d8f1b3c5e72'],
            'totalAmountInCents' => ['The total amount of the order, in cents.', 3500],
            'status' => ['The current status of the order.', 'placed'],
            'placedAt' => ['The date and time when the order was placed.', '2026-01-14T09:30:00+00:00'],
            'cancelledAt' => ['The date and time when the order was cancelled, if it was.', '2026-01-15T14:20:00+00:00'],
        ], self::describeSchemaProperties($client, 'Order'));
    }

    #[Test]
    public function itDocumentsTheShipmentProperties(): void
    {
        // Given
        $client = self::openApiClient();

        // When
        $client->request('GET', '/docs');

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
        ], self::describeSchemaProperties($client, 'Shipment'));
    }

    private static function openApiClient(): Client
    {
        return self::createClient([], ['headers' => ['Accept' => 'application/vnd.openapi+json']]);
    }

    /**
     * @return array<string, array{mixed, mixed}>
     */
    private static function describeSchemaProperties(Client $client, string $schemaName): array
    {
        self::assertResponseIsSuccessful();

        $response = $client->getResponse();
        Assert::notNull($response);

        $spec = self::toArray(json_decode((string) $response->getContent(), true));
        $schemas = self::toArray(self::toArray($spec['components'])['schemas']);
        $schema = self::toArray($schemas[$schemaName]);

        $described = [];

        foreach (self::toArray($schema['properties']) as $name => $property) {
            $property = self::toArray($property);
            $described[(string) $name] = [$property['description'] ?? null, $property['example'] ?? null];
        }

        return $described;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function toArray(mixed $value): array
    {
        Assert::isArray($value);

        return $value;
    }
}
