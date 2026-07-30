<?php

declare(strict_types=1);

namespace Web\Tests\Support;

use Bootstrap\Kernel;
use Support\Helpers\ServiceLocatorTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

abstract class AbstractWebTestCase extends WebTestCase
{
    use ServiceLocatorTrait;

    /**
     * @param array{environment?: string, debug?: bool} $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('test', false, 'web');
    }

    protected static function browser(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();

        return $client;
    }

    protected function csrfToken(string $tokenId): string
    {
        return $this->service(CsrfTokenManagerInterface::class)->getToken($tokenId)->getValue();
    }
}
