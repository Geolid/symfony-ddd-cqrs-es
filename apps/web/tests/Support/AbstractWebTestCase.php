<?php

declare(strict_types=1);

namespace Web\Tests\Support;

use Bootstrap\Kernel;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\Helpers\EventSourcingTrait;
use Support\Helpers\ServiceLocatorTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

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
        $form = $crawler->filter('main form')->form();
        $form->setValues(['login' => $login, 'password' => $password]);
        $client->submit($form);
    }

    /**
     * Registers an Identity + PasswordCredential + linked Customer, and logs in as them.
     */
    protected function loggedInCustomer(KernelBrowser $client, string $login = 'buyer@example.com'): string
    {
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(PasswordCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
            ->withLogin($login)
            ->withPassword('correct horse battery staple')
            ->create());
        $customer = CustomerTestFactory::new()->linkedToIdentity($identity->id()->toString())->create();
        $this->store($customer);

        $this->logIn($client, $login, 'correct horse battery staple');

        return $customer->id()->toString();
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
