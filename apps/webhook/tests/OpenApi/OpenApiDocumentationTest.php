<?php

declare(strict_types=1);

namespace Webhook\Tests\OpenApi;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Webhook\Tests\Support\AbstractWebhookTestCase;
use Webmozart\Assert\Assert;

final class OpenApiDocumentationTest extends AbstractWebhookTestCase
{
    private const string PATH = '/webhooks/carrier-delivery';

    #[Test]
    public function itReturnsTheOpenApiSpecification(): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request('GET', '/docs.json');

        // Then
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
    }

    #[Test]
    public function itReturnsTheSwaggerUi(): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request('GET', '/docs');

        // Then
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('text/html', (string) $client->getResponse()->headers->get('Content-Type'));
        self::assertStringContainsString('swagger-ui', (string) $client->getResponse()->getContent());
    }

    #[Test]
    public function itDocumentsTheDeliveryEndpoint(): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request('GET', '/docs.json');

        // Then
        $operation = self::operation($client->getResponse());

        self::assertSame('Report a shipment as delivered.', $operation['summary']);
        self::assertSame([202, 400, 401, 404, 422], array_keys(self::toArray($operation['responses'])));
    }

    #[Test]
    public function itReferencesThePayloadSchema(): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request('GET', '/docs.json');

        // Then
        $operation = self::operation($client->getResponse());
        $schema = self::toArray(self::toArray(self::toArray($operation['requestBody'])['content'])['application/json'])['schema'];

        self::assertSame(['$ref' => '#/components/schemas/CarrierDeliveryPayload'], self::toArray($schema));
    }

    #[Test]
    public function itDocumentsEveryPayloadFieldWithDescriptionAndExample(): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request('GET', '/docs.json');

        // Then
        $schemas = self::toArray(self::toArray(self::decodeSpec($client->getResponse())['components'])['schemas']);
        $schema = self::toArray($schemas['CarrierDeliveryPayload']);

        self::assertSame([
            'shipmentId' => [
                'Identifier of the shipment the carrier has delivered.',
                '0195f2c4-8f7a-7c3e-9b1d-2a4c6e8f0a12',
            ],
        ], self::describeProperties($schema));
        self::assertContains('shipmentId', self::toArray($schema['required']));
    }

    #[Test]
    public function itDeclaresTheGatewayServer(): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request('GET', '/docs.json');

        // Then
        $servers = self::toArray(self::decodeSpec($client->getResponse())['servers']);

        self::assertSame('/webhook', self::toArray($servers[0])['url']);
    }

    #[Test]
    public function itDocumentsTheSignatureHeaderParameter(): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request('GET', '/docs.json');

        // Then
        $signature = self::findSignatureParameter(self::toArray(self::operation($client->getResponse())['parameters']));

        self::assertSame('header', $signature['in']);
        self::assertTrue($signature['required']);
        self::assertSame(['type' => 'string'], self::toArray($signature['schema']));
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function operation(Response $response): array
    {
        $paths = self::toArray(self::decodeSpec($response)['paths']);

        return self::toArray(self::toArray($paths[self::PATH])['post']);
    }

    /**
     * @param array<array-key, mixed> $parameters
     *
     * @return array<array-key, mixed>
     */
    private static function findSignatureParameter(array $parameters): array
    {
        foreach ($parameters as $parameter) {
            $parameter = self::toArray($parameter);

            if ('X-Carrier-Signature' === ($parameter['name'] ?? null)) {
                return $parameter;
            }
        }

        self::fail('The X-Carrier-Signature header parameter is not documented.');
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeSpec(Response $response): array
    {
        return self::toArray(json_decode((string) $response->getContent(), true));
    }

    /**
     * @param array<array-key, mixed> $schema
     *
     * @return array<string, array{mixed, mixed}>
     */
    private static function describeProperties(array $schema): array
    {
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
