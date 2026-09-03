<?php

declare(strict_types=1);

namespace Webhook\Tests\OpenApi;

use PHPUnit\Framework\Attributes\Test;
use Webhook\Tests\Support\AbstractWebhookTestCase;

final class OpenApiDocumentationTest extends AbstractWebhookTestCase
{
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
        $response = $client->getResponse();
        $contentType = (string) $response->headers->get('Content-Type');
        self::assertStringContainsString('text/html', $contentType);
        $content = (string) $response->getContent();
        self::assertStringContainsString('swagger-ui', $content);
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
}
