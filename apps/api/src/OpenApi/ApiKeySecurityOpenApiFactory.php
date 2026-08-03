<?php

declare(strict_types=1);

namespace Api\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final readonly class ApiKeySecurityOpenApiFactory implements OpenApiFactoryInterface
{
    private const string SCHEME_NAME = 'ApiKey';

    public function __construct(
        #[AutowireDecorated]
        private OpenApiFactoryInterface $inner,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->inner)($context);

        $securitySchemes = $openApi->getComponents()->getSecuritySchemes() ?? new \ArrayObject();
        $securitySchemes[self::SCHEME_NAME] = new SecurityScheme(
            type: 'apiKey',
            description: 'API key to pass via the X-API-KEY header.',
            name: 'X-API-KEY',
            in: 'header',
        );

        return $openApi
            ->withComponents($openApi->getComponents()->withSecuritySchemes($securitySchemes))
            ->withSecurity([[self::SCHEME_NAME => []]]);
    }
}
