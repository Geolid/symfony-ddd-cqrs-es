<?php

declare(strict_types=1);

namespace Api\Tests\OpenApi;

use Api\OpenApi\ApiKeySecurityOpenApiFactory;
use Api\Tests\Support\AbstractApiTestCase;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\OpenApi;
use PHPUnit\Framework\Attributes\Test;

final class ApiKeySecurityOpenApiFactoryTest extends AbstractApiTestCase
{
    #[Test]
    public function itAddsTheApiKeySchemeWithoutLosingAnExistingOne(): void
    {
        // Given
        $inner = $this->serviceAs('Api\OpenApi\ApiKeySecurityOpenApiFactory.inner', OpenApiFactoryInterface::class);
        $factory = new ApiKeySecurityOpenApiFactory(new DummyOpenApiFactoryWithAnExistingScheme($inner));

        // When
        $openApi = $factory([]);

        // Then
        $schemes = $openApi->getComponents()->getSecuritySchemes();
        self::assertNotNull($schemes);
        self::assertArrayHasKey('OAuth2', $schemes->getArrayCopy());
        self::assertArrayHasKey('ApiKey', $schemes->getArrayCopy());
        self::assertSame([['ApiKey' => []]], $openApi->getSecurity());
    }
}

final class DummyOpenApiFactoryWithAnExistingScheme implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $inner)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->inner)($context);

        $schemes = $openApi->getComponents()->getSecuritySchemes() ?? new \ArrayObject();
        $schemes['OAuth2'] = new SecurityScheme(type: 'oauth2', description: 'A pre-existing scheme.');

        return $openApi->withComponents($openApi->getComponents()->withSecuritySchemes($schemes));
    }
}
