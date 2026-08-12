<?php

declare(strict_types=1);

namespace Web\Tests\Support;

use Bootstrap\Kernel;
use Iam\Access\Application\Finder\Grant\GrantResult;
use Iam\Access\Application\Query\ListGrantsForIdentity\ListGrantsForIdentity;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Support\Helpers\EventSourcingTrait;
use Support\Helpers\ServiceLocatorTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Web\Security\PasswordUser;

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

    /**
     * @throws ApplicationExceptionInterface
     */
    protected function loginAs(KernelBrowser $client, Identity $identity, string $login = 'test@example.com'): void
    {
        $identityId = $identity->id()->toString();
        $grants = [];

        foreach ($this->service(QueryBusInterface::class)->ask(new ListGrantsForIdentity($identityId)) as $grant) {
            \assert($grant instanceof GrantResult);
            $grants[] = $grant->permission;
        }

        // A lazy firewall re-resolves the user (refreshUser()) on any request that actually
        // touches security — a real PasswordCredential must exist or that refresh deauthenticates.
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withLogin($login)
            ->withHasher($this->service(SecretHasherInterface::class))
            ->store();

        $client->loginUser(new PasswordUser($identityId, $login, $grants));
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
