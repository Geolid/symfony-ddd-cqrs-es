<?php

declare(strict_types=1);

namespace Storefront\Tests\Support;

use Bootstrap\Kernel;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
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

    protected function logIn(KernelBrowser $client, string $email, string $password): void
    {
        $crawler = $client->request('GET', $this->path('security_login', ['email' => $email]));
        $form = $crawler->filter('[data-testid="password-form"]')->form();
        $form->setValues(['password' => $password]);
        $client->submit($form);
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    protected function givenPasswordCredential(string $identityId, ?string $password = null): void
    {
        $credential = PasswordCredentialBuilder::new()
            ->withIdentityId($identityId)
            ->withPassword($password ?? 'MyStr0ngP@ssw0rd123!')
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->create();
        $this->store($credential);
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    protected function loginAs(KernelBrowser $client, IdentityBuilder $builder): Identity
    {
        $identity = $builder->create();
        $this->store($identity);

        // A lazy firewall re-resolves the user (refreshUser()) on any request that actually
        // touches security — a real, authenticatable PasswordCredential must exist or that refresh deauthenticates.
        $this->givenPasswordCredential($identity->id->toString());

        $client->loginUser($this->service(PasswordUserProvider::class)->loadUserByIdentifier($builder['email']->value));

        return $identity;
    }
}
