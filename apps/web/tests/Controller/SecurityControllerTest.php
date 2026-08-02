<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
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
            ->forIdentity($identity->id()->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->create());

        // When
        $crawler = $client->request('GET', '/login');
        $form = $crawler->filter('form')->form();
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
            ->forIdentity($identity->id()->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->create());

        // When
        $crawler = $client->request('GET', '/login');
        $form = $crawler->filter('form')->form();
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
            ->forIdentity($identity->id()->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->create());

        // When
        $crawler = $client->request('GET', '/login');
        $form = $crawler->filter('form')->form();
        $form->setValues(['login' => 'buyer@example.com', 'password' => 'correct horse battery staple']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="login-error"]');
    }

    #[Test]
    public function itLogsOut(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(PasswordCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->create());
        $crawler = $client->request('GET', '/login');
        $form = $crawler->filter('form')->form();
        $form->setValues(['login' => 'buyer@example.com', 'password' => 'correct horse battery staple']);
        $client->submit($form);

        // When
        $client->request('GET', '/logout');

        // Then
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="nav-login"]');
    }
}
