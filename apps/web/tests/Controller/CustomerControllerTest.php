<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Web\Tests\Support\AbstractWebTestCase;

final class CustomerControllerTest extends AbstractWebTestCase
{
    #[Test]
    public function itRegistersACustomerAndLetsThemLogIn(): void
    {
        // Given
        $client = self::browser();

        // When
        $this->registerCustomer($client, 'buyer-1@example.com', 'correct horse battery staple');

        // Then
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="flash-success"]');

        $this->logIn($client, 'buyer-1@example.com', 'correct horse battery staple');
        self::assertResponseRedirects('/sales/orders');
    }

    #[Test]
    public function itRefusesAnAddressAlreadyRegistered(): void
    {
        // Given
        $client = self::browser();
        $this->registerCustomer($client, 'buyer-2@example.com', 'correct horse battery staple');

        // When
        $crawler = $client->request('GET', '/sales/customers/register');
        $form = $crawler->filter('main form')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[email]', $prefix) => 'buyer-2@example.com',
            \sprintf('%s[password]', $prefix) => 'another password entirely',
        ]);
        $client->submit($form);

        // Then
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="flash-error"]');
    }

    #[Test]
    public function itErasesTheLoggedInCustomer(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client, 'buyer-3@example.com');

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
        $this->loggedInCustomer($client, 'buyer-4@example.com');

        // When
        $client->request('POST', '/sales/customers/erase', ['_token' => 'invalid']);

        // Then
        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function itChangesTheLoggedInCustomersPassword(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client, 'buyer-5@example.com');

        // When
        $crawler = $client->request('GET', '/sales/customers/profile');
        $form = $crawler->filter('main form')->form();
        $prefix = $form->getName();
        $form->setValues([\sprintf('%s[password]', $prefix) => 'a brand new password']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects('/sales/orders');
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="flash-success"]');

        $client->request('GET', '/logout');
        $this->logIn($client, 'buyer-5@example.com', 'a brand new password');
        self::assertResponseRedirects('/sales/orders');
    }

    private function registerCustomer(KernelBrowser $client, string $email, string $password): void
    {
        $crawler = $client->request('GET', '/sales/customers/register');
        $form = $crawler->filter('main form')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[email]', $prefix) => $email,
            \sprintf('%s[password]', $prefix) => $password,
        ]);
        $client->submit($form);
    }
}
