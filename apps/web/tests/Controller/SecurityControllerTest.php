<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
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
        $identity = IdentityBuilder::new()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('MyStr0ngP@ssw0rd123!')
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->create();
        $customer = CustomerBuilder::new()->withId($identity->id->toString())->create();
        $this->store($identity, $passwordCredential, $customer);

        // When
        $crawler = $client->request('GET', $this->path('security_login'));
        $form = $crawler->filter('[data-testid="login-form"]')->form();
        $form->setValues(['login' => 'buyer@example.com', 'password' => 'MyStr0ngP@ssw0rd123!']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('sales_order_list'));
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="nav-logout"]');
    }

    #[Test]
    public function itLogsInWithRememberMe(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withLogin('buyer-remember@example.com')
            ->withPassword('MyStr0ngP@ssw0rd123!')
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->create();
        $customer = CustomerBuilder::new()->withId($identity->id->toString())->create();
        $this->store($identity, $passwordCredential, $customer);

        // When
        $crawler = $client->request('GET', $this->path('security_login'));
        $form = $crawler->filter('[data-testid="login-form"]')->form();
        $form->setValues(['login' => 'buyer-remember@example.com', 'password' => 'MyStr0ngP@ssw0rd123!', '_remember_me' => true]);
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('sales_order_list'));
        self::assertNotNull($client->getCookieJar()->get('REMEMBERME'));
    }

    #[Test]
    public function itRefusesAnIncorrectPassword(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityBuilder::new()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('MyStr0ngP@ssw0rd123!')
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->create();
        $this->store($identity, $passwordCredential);

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
        $identity = IdentityBuilder::new()->suspended()->create();
        $passwordCredential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('MyStr0ngP@ssw0rd123!')
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->create();
        $this->store($passwordCredential, $identity);

        // When
        $crawler = $client->request('GET', $this->path('security_login'));
        $form = $crawler->filter('[data-testid="login-form"]')->form();
        $form->setValues(['login' => 'buyer@example.com', 'password' => 'MyStr0ngP@ssw0rd123!']);
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
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $this->loginAs($client, $identity);

        // When
        $client->request('GET', $this->path('_logout_main'));

        // Then
        $client->followRedirect();
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="nav-login"]');
    }
}
