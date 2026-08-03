<?php

declare(strict_types=1);

namespace Webhook\Tests\OpenApi;

use Symfony\Component\HttpFoundation\Response;
use Webhook\Tests\Support\AbstractWebhookTestCase;

abstract class AbstractWebhookOpenApiDocumentationTestCase extends AbstractWebhookTestCase
{
    abstract protected static function path(): string;

    abstract protected static function signatureHeaderName(): string;

    /**
     * @return array<array-key, mixed>
     */
    protected static function operation(Response $response): array
    {
        $paths = self::toArray(self::decodeSpec($response)['paths']);

        return self::toArray(self::toArray($paths[static::path()])['post']);
    }

    /**
     * @param array<array-key, mixed> $parameters
     *
     * @return array<array-key, mixed>
     */
    protected static function findSignatureParameter(array $parameters): array
    {
        foreach ($parameters as $parameter) {
            $parameter = self::toArray($parameter);

            if (static::signatureHeaderName() === ($parameter['name'] ?? null)) {
                return $parameter;
            }
        }

        self::fail(\sprintf('The %s header parameter is not documented.', static::signatureHeaderName()));
    }

    /**
     * @param array<array-key, mixed> $schema
     *
     * @return array<string, array{mixed, mixed}>
     */
    protected static function describeProperties(array $schema): array
    {
        $described = [];

        foreach (self::toArray($schema['properties']) as $name => $property) {
            $property = self::toArray($property);
            $described[(string) $name] = [$property['description'] ?? null, $property['example'] ?? null];
        }

        return $described;
    }
}
