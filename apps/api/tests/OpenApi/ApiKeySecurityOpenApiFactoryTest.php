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
        $schemeNames = $schemes->getArrayCopy();
        self::assertArrayHasKey('OAuth2', $schemeNames);
        self::assertArrayHasKey('ApiKey', $schemeNames);
        $security = $openApi->getSecurity();
        self::assertSame([['ApiKey' => []]], $security);
    }
}

final readonly class DummyOpenApiFactoryWithAnExistingScheme implements OpenApiFactoryInterface
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
