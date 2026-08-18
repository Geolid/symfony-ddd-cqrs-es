<?php

declare(strict_types=1);

namespace Api\Tests\OpenApi;

use Api\Tests\Support\AbstractApiTestCase;
use Webmozart\Assert\Assert;

abstract class AbstractResourcePropertyDocumentationTestCase extends AbstractApiTestCase
{
    /**
     * @return array<string, array{mixed, mixed}>
     */
    protected static function describeSchemaProperties(string $schemaName): array
    {
        $client = self::createClient([], ['headers' => ['Accept' => 'application/vnd.openapi+json']]);
        $client->request('GET', '/docs');

        self::assertResponseIsSuccessful();

        $response = $client->getResponse();
        Assert::notNull($response);

        $spec = self::toArray(json_decode($response->getContent(), true));
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
