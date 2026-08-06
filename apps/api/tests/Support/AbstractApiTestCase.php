<?php

declare(strict_types=1);

namespace Api\Tests\Support;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Bootstrap\Kernel;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Support\Helpers\EventSourcingTrait;
use Support\Helpers\ServiceLocatorTrait;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractApiTestCase extends ApiTestCase
{
    use EventSourcingTrait;
    use ServiceLocatorTrait;

    protected static ?bool $alwaysBootKernel = false;

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
        $identifier = 'key_'.bin2hex(random_bytes(4));
        $secret = bin2hex(random_bytes(16));
        $this->store(ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withIdentifier($identifier)
            ->withSecret($secret)
            ->create());

        foreach ($permissions as $permission) {
            $this->store(GrantTestFactory::new()->withIdentityId($identity->id()->toString())->withPermission($permission)->create());
        }

        return self::clientWithApiKey(\sprintf('%s.%s', $identifier, $secret));
    }

    protected static function malformedApiKeyClient(): Client
    {
        return self::clientWithApiKey(bin2hex(random_bytes(8)));
    }

    protected function invalidApiKeyClient(Identity $identity): Client
    {
        $identifier = 'key_'.bin2hex(random_bytes(4));
        $this->store(ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withIdentifier($identifier)
            ->create());

        return self::clientWithApiKey(\sprintf('%s.%s', $identifier, bin2hex(random_bytes(16))));
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
