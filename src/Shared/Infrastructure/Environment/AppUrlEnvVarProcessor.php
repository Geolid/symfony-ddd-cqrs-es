<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Environment;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Webmozart\Assert\Assert;

final readonly class AppUrlEnvVarProcessor implements EnvVarProcessorInterface
{
    public function __construct(
        #[Autowire('%kernel.app_id%')]
        private ?string $appId,
    ) {
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        $defaultUri = $getEnv($name);
        Assert::string($defaultUri);

        if ('prod' !== $getEnv('APP_ENV')) {
            return $defaultUri;
        }

        if (null === $this->appId) {
            throw new \LogicException('The "app_url" env var processor requires an App ID (kernel.app_id) to build the per-DM URL in prod.');
        }

        return \sprintf(
            '%s://%s.%s',
            parse_url($defaultUri, \PHP_URL_SCHEME),
            $this->appId,
            parse_url($defaultUri, \PHP_URL_HOST),
        );
    }

    public static function getProvidedTypes(): array
    {
        return ['app_url' => 'string'];
    }
}
