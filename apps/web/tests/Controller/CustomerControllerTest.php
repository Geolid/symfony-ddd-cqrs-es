<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Iam\Identity\Domain\ValueObject\Login;
use Iam\Identity\Domain\ValueObject\PasswordCredentialUniqueValue;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Domain\Service\UniqueValueRegistryInterface;
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
        $this->registerCustomer($client, 'buyer-1', 'buyer-1@example.com', 'correct horse battery staple');

        // Then
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="flash-success"]');

        $this->logIn($client, 'buyer-1', 'correct horse battery staple');
        self::assertResponseRedirects('/sales/orders');
    }

    #[Test]
    public function itRefusesToRegisterAnAddressAlreadyRegistered(): void
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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(CustomerTestFactory::new()->withEmail('buyer-3@example.com')->linkedToIdentity($identity->id()->toString())->create());
        $this->loginAs($client, $identity, 'buyer-3');
        $fingerprint = Login::fromString('buyer-3')->fingerprint();
        $this->service(UniqueValueRegistryInterface::class)->reserve(PasswordCredentialUniqueValue::LOGIN, $fingerprint);

        // When
        $client->request('POST', '/sales/customers/erase', [
            '_token' => $this->csrfToken($client, 'erase-customer'),
        ]);

        // Then
        self::assertResponseRedirects('/logout');
        self::assertFalse($this->service(UniqueValueRegistryInterface::class)->exists(PasswordCredentialUniqueValue::LOGIN, $fingerprint));
    }

    #[Test]
    public function itRefusesToEraseWithAnInvalidCsrfToken(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(CustomerTestFactory::new()->withEmail('buyer-4@example.com')->linkedToIdentity($identity->id()->toString())->create());
        $this->loginAs($client, $identity);

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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(CustomerTestFactory::new()->withEmail('buyer-5@example.com')->linkedToIdentity($identity->id()->toString())->create());
        $this->loginAs($client, $identity, 'buyer-5@example.com');

        // When
        $crawler = $client->request('GET', '/sales/customers/profile');
        $form = $crawler->filter('[data-testid="change-password-form"]')->form();
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

    private function registerCustomer(KernelBrowser $client, string $login, string $email, string $password): void
    {
        $crawler = $client->request('GET', '/sales/customers/register');
        $form = $crawler->filter('[data-testid="register-customer-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[login]', $prefix) => $login,
            \sprintf('%s[email]', $prefix) => $email,
            \sprintf('%s[password]', $prefix) => $password,
        ]);
        $client->submit($form);
    }
}
