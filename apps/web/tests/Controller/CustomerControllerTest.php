<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Web\Tests\Support\AbstractWebTestCase;

final class CustomerControllerTest extends AbstractWebTestCase
{
    #[Test]
    #[DataProvider('provideLocalizedRegisterPath')]
    public function itShowsRegister(string $locale, string $path): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', $path);

        // Then
        self::assertResponseIsSuccessful();
        self::assertSame($locale, $client->getRequest()->getLocale());
        self::assertSelectorExists('[data-testid="register-customer-form"]');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideLocalizedRegisterPath(): iterable
    {
        yield 'en' => ['en', '/sales/customers/register'];
        yield 'fr' => ['fr', '/ventes/clients/inscription'];
    }

    #[Test]
    #[DataProvider('provideLocalizedProfilePath')]
    public function itShowsProfile(string $locale, string $path): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->store();
        CustomerTestFactory::new()->withId($identity->id()->toString())->withEmail('buyer-locale@example.com')->store();
        $this->loginAs($client, $identity);

        // When
        $client->request('GET', $path);

        // Then
        self::assertResponseIsSuccessful();
        self::assertSame($locale, $client->getRequest()->getLocale());
        self::assertSelectorExists('[data-testid="change-password-form"]');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideLocalizedProfilePath(): iterable
    {
        yield 'en' => ['en', '/sales/customers/profile'];
        yield 'fr' => ['fr', '/ventes/clients/profil'];
    }

    #[Test]
    public function itRegistersAndLetsThemLogIn(): void
    {
        // Given
        $client = self::browser();

        // When
        $this->registerCustomer($client, 'buyer-1', 'buyer-1@example.com', 'correct horse battery staple');

        // Then
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="flash-success"]');

        $this->logIn($client, 'buyer-1', 'correct horse battery staple');
        self::assertResponseRedirects('/sales/orders');
    }

    #[Test]
    public function itRefusesToRegisterAnAlreadyRegisteredEmail(): void
    {
        // Given
        $client = self::browser();
        $this->registerCustomer($client, 'buyer-2', 'buyer-2@example.com', 'correct horse battery staple');

        // When
        $crawler = $client->request('GET', '/sales/customers/register');
        $form = $crawler->filter('[data-testid="register-customer-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[login]', $prefix) => 'buyer-2-retry',
            \sprintf('%s[email]', $prefix) => 'buyer-2@example.com',
            \sprintf('%s[password][first]', $prefix) => 'another password entirely',
            \sprintf('%s[password][second]', $prefix) => 'another password entirely',
        ]);
        $client->submit($form);

        // Then
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="flash-error"]');
    }

    #[Test]
    public function itRefusesAMismatchedPasswordConfirmation(): void
    {
        // Given
        $client = self::browser();

        // When
        $crawler = $client->request('GET', '/sales/customers/register');
        $form = $crawler->filter('[data-testid="register-customer-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[login]', $prefix) => 'buyer-7',
            \sprintf('%s[email]', $prefix) => 'buyer-7@example.com',
            \sprintf('%s[password][first]', $prefix) => 'correct horse battery staple',
            \sprintf('%s[password][second]', $prefix) => 'a different password entirely',
        ]);
        $client->submit($form);

        // Then
        self::assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function itFreesTheLoginAfterAFailedRegistration(): void
    {
        // Given
        $client = self::browser();
        $this->registerCustomer($client, 'buyer-6-taken', 'buyer-6@example.com', 'correct horse battery staple');
        $this->registerCustomer($client, 'buyer-6-retry', 'buyer-6@example.com', 'another password entirely');

        // When
        $this->registerCustomer($client, 'buyer-6-retry', 'buyer-6-again@example.com', 'yet another Password!42');

        // Then
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="flash-success"]');
    }

    #[Test]
    public function itErases(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->store();
        CustomerTestFactory::new()->withId($identity->id()->toString())->withEmail('buyer-3@example.com')->store();
        $this->loginAs($client, $identity);

        // When
        $client->request('POST', '/sales/customers/erase', [
            '_token' => $this->csrfToken($client, 'erase-customer'),
        ]);

        // Then
        self::assertResponseRedirects('/logout');
    }

    #[Test]
    public function itRefusesToEraseWithAnInvalidCsrfToken(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->store();
        CustomerTestFactory::new()->withId($identity->id()->toString())->withEmail('buyer-4@example.com')->store();
        $this->loginAs($client, $identity);

        // When
        $client->request('POST', '/sales/customers/erase', ['_token' => 'invalid']);

        // Then
        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function itChangesThePassword(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->store();
        CustomerTestFactory::new()->withId($identity->id()->toString())->withEmail('buyer-5@example.com')->store();
        $this->loginAs($client, $identity, 'buyer-5@example.com');

        // When
        $crawler = $client->request('GET', '/sales/customers/profile');
        $form = $crawler->filter('[data-testid="change-password-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([\sprintf('%s[password]', $prefix) => 'A Brand New Password!42']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects('/sales/orders');
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="flash-success"]');

        $client->request('GET', '/logout');
        $this->logIn($client, 'buyer-5@example.com', 'A Brand New Password!42');
        self::assertResponseRedirects('/sales/orders');
    }

    private function registerCustomer(KernelBrowser $client, string $login, string $email, string $password): void
    {
        $crawler = $client->request('GET', '/sales/customers/register');
        $form = $crawler->filter('[data-testid="register-customer-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[login]', $prefix) => $login,
            \sprintf('%s[email]', $prefix) => $email,
            \sprintf('%s[password][first]', $prefix) => $password,
            \sprintf('%s[password][second]', $prefix) => $password,
        ]);
        $client->submit($form);
    }
}
