<?php

declare(strict_types=1);

namespace Api\Tests\Support;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Bootstrap\Kernel;
use Iam\Authentication\Application\ApiKey\ApiKeyGenerator;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use Support\TestCase\EventSourcingTrait;
use Support\TestCase\ServiceLocatorTrait;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractApiTestCase extends ApiTestCase
{
    use EventSourcingTrait;
    use ServiceLocatorTrait;

    #[\Override]
    protected static ?bool $alwaysBootKernel = false;

    private ApiKeyGenerator $apiKeyGenerator;
    private ApiKeyHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKeyGenerator = $this->service(ApiKeyGenerator::class);
        $this->hasher = $this->service(ApiKeyHasherInterface::class);
    }

    /**
     * @param array{environment?: string, debug?: bool} $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel(
            $options['environment'] ?? 'test',
            $options['debug'] ?? (bool) ($_SERVER['APP_DEBUG'] ?? true),
            'api',
        );
    }

    protected static function unauthenticatedClient(): Client
    {
        return self::clientWithApiKey(null);
    }

    protected function authenticatedClient(Identity $identity): Client
    {
        $apiKey = $this->apiKeyGenerator->generate();
        $credential = ApiKeyCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withKeyId($apiKey->keyId)
            ->withSecret($apiKey->secret)
            ->withHasher($this->hasher)
            ->create();
        $this->store($credential);

        return self::clientWithApiKey(\sprintf('%s.%s', $apiKey->keyId, $apiKey->secret));
    }

    protected static function malformedApiKeyClient(): Client
    {
        return self::clientWithApiKey('malformed-api-key');
    }

    protected function invalidApiKeyClient(Identity $identity): Client
    {
        $apiKey = $this->apiKeyGenerator->generate();
        $credential = ApiKeyCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withKeyId($apiKey->keyId)
            ->withHasher($this->hasher)
            ->create();
        $this->store($credential);

        return self::clientWithApiKey(\sprintf('%s.%s', $apiKey->keyId, $this->apiKeyGenerator->generate()->secret));
    }

    protected function revokedApiKeyClient(Identity $identity): Client
    {
        $apiKey = $this->apiKeyGenerator->generate();
        $credential = ApiKeyCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withKeyId($apiKey->keyId)
            ->withSecret($apiKey->secret)
            ->withHasher($this->hasher)
            ->revoked()
            ->create();
        $this->store($credential);

        return self::clientWithApiKey(\sprintf('%s.%s', $apiKey->keyId, $apiKey->secret));
    }

    private static function clientWithApiKey(?string $apiKey): Client
    {
        $headers = ['Accept' => 'application/ld+json'];
        if (null !== $apiKey) {
            $headers['X-Api-Key'] = $apiKey;
        }

        return static::createClient([], ['headers' => $headers]);
    }
}
