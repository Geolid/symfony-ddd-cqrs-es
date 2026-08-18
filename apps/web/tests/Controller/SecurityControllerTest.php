<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Iam\Identity\Domain\Service\PasswordPolicyInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Web\Tests\Support\AbstractWebTestCase;

final class SecurityControllerTest extends AbstractWebTestCase
{
    #[Test]
    #[DataProvider('provideLocalizedPath')]
    public function itShowsLogin(string $locale, string $path): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', $path);

        // Then
        self::assertResponseIsSuccessful();
        self::assertSame($locale, $client->getRequest()->getLocale());
        self::assertSelectorExists('[data-testid="login-form"]');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideLocalizedPath(): iterable
    {
        yield 'en' => ['en', '/login'];
        yield 'fr' => ['fr', '/connexion'];
    }

    #[Test]
    public function itLogsIn(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->store();
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->withPolicy($this->service(PasswordPolicyInterface::class))
            ->store();
        CustomerTestFactory::new()->withId($identity->id->toString())->store();

        // When
        $crawler = $client->request('GET', $this->path('security_login'));
        $form = $crawler->filter('[data-testid="login-form"]')->form();
        $form->setValues(['login' => 'buyer@example.com', 'password' => 'correct horse battery staple']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('sales_order_list'));
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="nav-logout"]');
    }

    #[Test]
    public function itRefusesAnIncorrectPassword(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->store();
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->withPolicy($this->service(PasswordPolicyInterface::class))
            ->store();

        // When
        $crawler = $client->request('GET', $this->path('security_login'));
        $form = $crawler->filter('[data-testid="login-form"]')->form();
        $form->setValues(['login' => 'buyer@example.com', 'password' => 'wrong password']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('security_login'));
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="login-error"]');
        self::assertSelectorExists('[data-testid="nav-login"]');
    }

    #[Test]
    public function itRefusesASuspendedIdentity(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->suspended()->store();
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->withPolicy($this->service(PasswordPolicyInterface::class))
            ->store();

        // When
        $crawler = $client->request('GET', $this->path('security_login'));
        $form = $crawler->filter('[data-testid="login-form"]')->form();
        $form->setValues(['login' => 'buyer@example.com', 'password' => 'correct horse battery staple']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('security_login'));
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="login-error"]');
    }

    #[Test]
    public function itLogsOut(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->store();
        $this->loginAs($client, $identity);

        // When
        $client->request('GET', $this->path('_logout_main'));

        // Then
        $client->followRedirect();
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="nav-login"]');
    }
}
