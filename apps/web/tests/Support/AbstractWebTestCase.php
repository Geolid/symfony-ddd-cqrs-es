<?php

declare(strict_types=1);

namespace Web\Tests\Support;

use Bootstrap\Kernel;
use Iam\Identity\Domain\Identity;
use Support\Helpers\EventSourcingTrait;
use Support\Helpers\ServiceLocatorTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Web\Security\IamUser;

abstract class AbstractWebTestCase extends WebTestCase
{
    use EventSourcingTrait;
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

    protected function logIn(KernelBrowser $client, string $login, string $password): void
    {
        $crawler = $client->request('GET', '/login');
        $form = $crawler->filter('[data-testid="login-form"]')->form();
        $form->setValues(['login' => $login, 'password' => $password]);
        $client->submit($form);
    }

    protected function loginAs(KernelBrowser $client, Identity $identity): void
    {
        $client->loginUser(new IamUser($identity->id()->toString()));
    }

    protected function csrfToken(KernelBrowser $client, string $tokenId): string
    {
        $session = $client->getSession();

        if (null === $session) {
            throw new \LogicException('No session available in the test client. Check your "framework.test" config.');
        }

        $tokenValue = 'dummy-token';

        $session->set('_csrf/'.$tokenId, $tokenValue);
        $session->save();

        return $tokenValue;
    }
}
