<?php

declare(strict_types=1);

namespace Storefront\Tests\Support;

use Bootstrap\Kernel;
use Support\TestCase\EventSourcingTrait;
use Support\TestCase\ServiceLocatorTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

abstract class AbstractStorefrontTestCase extends WebTestCase
{
    use EventSourcingTrait;
    use ServiceLocatorTrait;

    /**
     * @param array{environment?: string, debug?: bool} $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel(
            $options['environment'] ?? 'test',
            $options['debug'] ?? (bool) ($_SERVER['APP_DEBUG'] ?? true),
            'storefront',
        );
    }

    protected static function browser(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();

        return $client;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function path(string $route, array $params = []): string
    {
        return $this->service(UrlGeneratorInterface::class)->generate($route, $params);
    }

    protected function loginAs(KernelBrowser $client, string $email): void
    {
        $client->loginUser($this->service(UserProviderInterface::class)->loadUserByIdentifier($email));
    }

    protected function seedCsrfToken(KernelBrowser $client, string $tokenId, string $token): void
    {
        $session = $client->getSession();
        \assert($session instanceof SessionInterface);
        $session->set('_csrf/'.$tokenId, $token);
        $session->save();
    }
}
