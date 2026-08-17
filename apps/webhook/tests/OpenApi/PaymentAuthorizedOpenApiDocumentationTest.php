<?php

declare(strict_types=1);

namespace Webhook\Tests\OpenApi;

use PHPUnit\Framework\Attributes\Test;
use Webhook\Webhook\PaymentAuthorizedParser;

final class PaymentAuthorizedOpenApiDocumentationTest extends AbstractWebhookOpenApiDocumentationTestCase
{
    #[Test]
    public function itDocumentsTheAuthorizationEndpoint(): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request('GET', '/docs.json');

        // Then
        $operation = self::operation($client->getResponse());

        self::assertSame(['Webhook'], self::toArray($operation['tags']));
        self::assertSame('Report an order payment as authorized.', $operation['summary']);
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

        self::assertSame(['$ref' => '#/components/schemas/PaymentAuthorizedPayload'], self::toArray($schema));
    }

    #[Test]
    public function itDocumentsPayloadFieldsWithDescriptionAndExample(): void
    {
        // Given
        $client = self::createClient();

        // When
        $client->request('GET', '/docs.json');

        // Then
        $schemas = self::toArray(self::toArray(self::decodeSpec($client->getResponse())['components'])['schemas']);
        $schema = self::toArray($schemas['PaymentAuthorizedPayload']);

        self::assertSame([
            'paymentReference' => [
                "The payment provider's own reference for the authorized charge.",
                'GLBX-9F3K2M1P',
            ],
        ], self::describeProperties($schema));
        self::assertContains('paymentReference', self::toArray($schema['required']));
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

    protected static function path(): string
    {
        return \sprintf('/webhooks/%s', PaymentAuthorizedParser::EVENT_TYPE);
    }

    protected static function signatureHeaderName(): string
    {
        return PaymentAuthorizedParser::SIGNATURE_HEADER;
    }
}
