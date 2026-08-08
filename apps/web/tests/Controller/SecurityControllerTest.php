<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Web\Tests\Support\AbstractWebTestCase;

final class SecurityControllerTest extends AbstractWebTestCase
{
    #[Test]
    public function itLogsInWithValidCredentials(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->create());
        $this->store(CustomerTestFactory::new()->withId($identity->id()->toString())->create());

        // When
        $crawler = $client->request('GET', '/login');
        $form = $crawler->filter('[data-testid="login-form"]')->form();
        $form->setValues(['login' => 'buyer@example.com', 'password' => 'correct horse battery staple']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects('/sales/orders');
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="nav-logout"]');
    }

    #[Test]
    public function itRefusesAnIncorrectPassword(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->create());

        // When
        $crawler = $client->request('GET', '/login');
        $form = $crawler->filter('[data-testid="login-form"]')->form();
        $form->setValues(['login' => 'buyer@example.com', 'password' => 'wrong password']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="login-error"]');
        self::assertSelectorExists('[data-testid="nav-login"]');
    }

    #[Test]
    public function itRefusesASuspendedIdentity(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);
        $this->store(PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->create());

        // When
        $crawler = $client->request('GET', '/login');
        $form = $crawler->filter('[data-testid="login-form"]')->form();
        $form->setValues(['login' => 'buyer@example.com', 'password' => 'correct horse battery staple']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="login-error"]');
    }

    #[Test]
    public function itGrantsAccessToThePermissionsHeldByTheIdentity(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(GrantTestFactory::new()->withIdentityId($identity->id()->toString())->withPermission('fixture.widget:read')->create());

        // When
        $this->loginAs($client, $identity);

        // Then
        $authorizationChecker = $this->service(AuthorizationCheckerInterface::class);
        self::assertTrue($authorizationChecker->isGranted('fixture.widget:read'));
        self::assertFalse($authorizationChecker->isGranted('fixture.widget:write'));
    }

    #[Test]
    public function itLogsOut(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->loginAs($client, $identity);

        // When
        $client->request('GET', '/logout');

        // Then
        $client->followRedirect();
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="nav-login"]');
    }
}
