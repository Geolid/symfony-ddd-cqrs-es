<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialUniqueKey;
use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Domain\ValueObject\CustomerUniqueKey;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
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
    public function itRegisters(): void
    {
        // Given
        $client = self::browser();

        // When
        $this->registerCustomer($client, 'buyer-1', 'buyer-1@example.com', 'MyStr0ngP@ssw0rd123!');

        // Then
        self::assertResponseRedirects($this->path('security_login'));
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="flash-success"]', 'sales.customer.flash.registered');

        $credential = $this->service(PasswordCredentialFinderInterface::class)->ofLogin('buyer-1');
        $customer = $this->service(CustomerFinderInterface::class)->ofId($credential->identityId);
        self::assertSame('buyer-1@example.com', $customer->email);
    }

    #[Test]
    public function itRefusesToRegisterAnAlreadyRegisteredEmail(): void
    {
        // Given
        $client = self::browser();
        $this->service(UniqueValueRegistryInterface::class)->reserve(UniqueKey::for(CustomerUniqueKey::EMAIL), 'buyer-2@example.com', Uuid::uuid7()->toString());

        // When
        $crawler = $client->request('GET', $this->path('sales_customer_register'));
        $form = $crawler->filter('[data-testid="register-customer-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[login]', $prefix) => 'buyer-2-retry',
            \sprintf('%s[email]', $prefix) => 'buyer-2@example.com',
            \sprintf('%s[password][first]', $prefix) => 'MyStr0ngP@ssw0rd123!',
            \sprintf('%s[password][second]', $prefix) => 'MyStr0ngP@ssw0rd123!',
        ]);
        $client->submit($form);

        // Then
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-testid="register-customer-form"]', 'already in use');
    }

    #[Test]
    public function itRefusesToRegisterAnAlreadyRegisteredLogin(): void
    {
        // Given
        $client = self::browser();
        $this->service(UniqueValueRegistryInterface::class)->reserve(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), 'buyer-10', Uuid::uuid7()->toString());

        // When
        $this->registerCustomer($client, 'buyer-10', 'buyer-10-other@example.com', 'MyStr0ngP@ssw0rd123!');

        // Then
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-testid="register-customer-form"]', 'already in use');
    }

    #[Test]
    public function itRefusesAMismatchedPasswordConfirmation(): void
    {
        // Given
        $client = self::browser();

        // When
        $crawler = $client->request('GET', $this->path('sales_customer_register'));
        $form = $crawler->filter('[data-testid="register-customer-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[login]', $prefix) => 'buyer-7',
            \sprintf('%s[email]', $prefix) => 'buyer-7@example.com',
            \sprintf('%s[password][first]', $prefix) => 'MyStr0ngP@ssw0rd123!',
            \sprintf('%s[password][second]', $prefix) => 'Xk9$mQ2vLp7&zR4w',
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
        $this->service(UniqueValueRegistryInterface::class)->reserve(UniqueKey::for(CustomerUniqueKey::EMAIL), 'buyer-6@example.com', Uuid::uuid7()->toString());

        // When
        $this->registerCustomer($client, 'buyer-6-retry', 'buyer-6@example.com', 'MyStr0ngP@ssw0rd123!');
        $this->registerCustomer($client, 'buyer-6-retry', 'buyer-6-again@example.com', 'Xk9$mQ2vLp7&zR4w');

        // Then
        self::assertResponseRedirects($this->path('security_login'));
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="flash-success"]', 'sales.customer.flash.registered');

        $credential = $this->service(PasswordCredentialFinderInterface::class)->ofLogin('buyer-6-retry');
        $customer = $this->service(CustomerFinderInterface::class)->ofId($credential->identityId);
        self::assertSame('buyer-6-again@example.com', $customer->email);
    }

    #[Test]
    public function itErases(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $customer = CustomerTestFactory::new()->withId($identity->id->toString())->withEmail('buyer-3@example.com')->create();
        $this->store($identity, $customer);
        $this->loginAs($client, $identity);

        // When
        $client->request('POST', $this->path('sales_customer_erase'), [
            '_token' => $this->csrfToken($client, 'erase-customer'),
        ]);

        // Then
        self::assertResponseRedirects($this->path('_logout_main'));
        $session = $client->getRequest()->getSession();
        \assert($session instanceof FlashBagAwareSessionInterface);
        self::assertSame(['sales.customer.flash.erased'], $session->getFlashBag()->get('success'));

        self::expectException(IdentityResultNotFoundException::class);
        $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
    }

    #[Test]
    public function itRefusesToEraseWithAnInvalidCsrfToken(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $customer = CustomerTestFactory::new()->withId($identity->id->toString())->withEmail('buyer-4@example.com')->create();
        $this->store($identity, $customer);
        $this->loginAs($client, $identity);

        // When
        $client->request('POST', $this->path('sales_customer_erase'), ['_token' => 'invalid']);

        // Then
        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    #[DataProvider('provideLocalizedProfilePath')]
    public function itShowsProfile(string $locale, string $path): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $customer = CustomerTestFactory::new()->withId($identity->id->toString())->withEmail('buyer-locale@example.com')->create();
        $this->store($identity, $customer);
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
    public function itChangesPassword(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $customer = CustomerTestFactory::new()->withId($identity->id->toString())->withEmail('buyer-5@example.com')->create();
        $this->store($identity, $customer);
        $this->loginAs($client, $identity, 'buyer-5@example.com');

        // When
        $crawler = $client->request('GET', $this->path('sales_customer_profile'));
        $form = $crawler->filter('[data-testid="change-password-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([\sprintf('%s[password]', $prefix) => 'Xk9$mQ2vLp7&zR4w']);
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('sales_order_list'));
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="flash-success"]', 'sales.customer.flash.password_changed');

        $credential = $this->service(PasswordCredentialFinderInterface::class)->ofIdentityId($identity->id->toString());
        self::assertTrue($this->service(PasswordHasherInterface::class)->verify($credential->passwordHash, 'Xk9$mQ2vLp7&zR4w'));
    }

    private function registerCustomer(KernelBrowser $client, string $login, string $email, string $password): void
    {
        $crawler = $client->request('GET', $this->path('sales_customer_register'));
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
