<?php

declare(strict_types=1);

namespace Api\Tests\Support;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Bootstrap\Kernel;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Helpers\ApiTokenTrait;
use Support\Helpers\EventSourcingTrait;
use Support\Helpers\ServiceLocatorTrait;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractApiTestCase extends ApiTestCase
{
    use ApiTokenTrait;
    use EventSourcingTrait;
    use ServiceLocatorTrait;

    protected static ?bool $alwaysBootKernel = false;

    private SecretHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(SecretHasherInterface::class);
    }

    /**
     * @param array{environment?: string, debug?: bool} $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('test', false, 'api');
    }

    protected static function unauthenticatedClient(): Client
    {
        return self::clientWithApiKey(null);
    }

    protected function authenticatedClient(Identity $identity, string ...$permissions): Client
    {
        $apiKey = $this->generateApiKey();
        $this->store(ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withIdentifier($apiKey->identifier)
            ->withSecret($apiKey->secret)
            ->withHasher($this->hasher)
            ->create());

        foreach ($permissions as $permission) {
            $this->store(GrantTestFactory::new()->withIdentityId($identity->id()->toString())->withPermission($permission)->create());
        }

        return self::clientWithApiKey(\sprintf('%s.%s', $apiKey->identifier, $apiKey->secret));
    }

    protected static function malformedApiKeyClient(): Client
    {
        return self::clientWithApiKey(bin2hex(random_bytes(8)));
    }

    protected function invalidApiKeyClient(Identity $identity): Client
    {
        $apiKey = $this->generateApiKey();
        $this->store(ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withIdentifier($apiKey->identifier)
            ->withHasher($this->hasher)
            ->create());

        return self::clientWithApiKey(\sprintf('%s.%s', $apiKey->identifier, $this->generateApiKey()->secret));
    }

    protected function revokedApiKeyClient(Identity $identity): Client
    {
        $apiKey = $this->generateApiKey();
        $this->store(ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withIdentifier($apiKey->identifier)
            ->withSecret($apiKey->secret)
            ->withHasher($this->hasher)
            ->revoked()
            ->create());

        return self::clientWithApiKey(\sprintf('%s.%s', $apiKey->identifier, $apiKey->secret));
    }

    protected function expiredApiKeyClient(Identity $identity): Client
    {
        $apiKey = $this->generateApiKey();
        $this->store(ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withIdentifier($apiKey->identifier)
            ->withSecret($apiKey->secret)
            ->withHasher($this->hasher)
            ->expired()
            ->create());

        return self::clientWithApiKey(\sprintf('%s.%s', $apiKey->identifier, $apiKey->secret));
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
