<?php

declare(strict_types=1);

namespace Storefront\Tests\Support;

use Bootstrap\Kernel;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Storefront\Security\PasswordUserProvider;
use Support\TestCase\EventSourcingTrait;
use Support\TestCase\ServiceLocatorTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    protected function logIn(KernelBrowser $client, string $login, string $password): void
    {
        $crawler = $client->request('GET', $this->path('security_login'));
        $form = $crawler->filter('[data-testid="login-form"]')->form();
        $form->setValues(['login' => $login, 'password' => $password]);
        $client->submit($form);
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    protected function givenPasswordCredential(string $identityId, ?string $login = null, ?string $password = null): string
    {
        $login ??= \sprintf('test-%s', $identityId);
        $password ??= 'MyStr0ngP@ssw0rd123!';

        $credential = PasswordCredentialBuilder::new()
            ->withIdentityId($identityId)
            ->withLogin($login)
            ->withPassword($password)
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->create();
        $this->store($credential);

        return $login;
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    protected function loginAs(KernelBrowser $client, Identity $identity, ?string $login = null): void
    {
        $identityId = $identity->id->toString();
        $login ??= \sprintf('test-%s', $identityId);

        // A lazy firewall re-resolves the user (refreshUser()) on any request that actually
        // touches security — a real PasswordCredential must exist or that refresh deauthenticates.
        $this->givenPasswordCredential($identityId, $login);

        $client->loginUser($this->service(PasswordUserProvider::class)->loadUserByIdentifier($login));
    }
}
