<?php

declare(strict_types=1);

namespace Api\Tests\Support;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Bootstrap\Kernel;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
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

    protected static function jsonClient(): Client
    {
        return static::createClient([], ['headers' => ['Accept' => 'application/ld+json']]);
    }

    /**
     * Registers an Identity + ApiTokenCredential granted the given permissions, and returns a
     * client authenticated as them.
     */
    protected function authenticatedClient(string ...$permissions): Client
    {
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        $identifier = 'key_'.bin2hex(random_bytes(4));
        $secret = bin2hex(random_bytes(16));
        $this->store(ApiTokenCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
            ->withIdentifier($identifier)
            ->withSecret($secret)
            ->create());

        foreach ($permissions as $permission) {
            $this->store(GrantTestFactory::new()->forIdentity($identity->id()->toString())->withPermission($permission)->create());
        }

        return static::createClient([], [
            'headers' => [
                'Accept' => 'application/ld+json',
                'X-Api-Key' => \sprintf('%s.%s', $identifier, $secret),
            ],
        ]);
    }
}
