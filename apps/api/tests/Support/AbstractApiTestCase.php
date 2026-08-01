<?php

declare(strict_types=1);

namespace Api\Tests\Support;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Bootstrap\Kernel;
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
}
