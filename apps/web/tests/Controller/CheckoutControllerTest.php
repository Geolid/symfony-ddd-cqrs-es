<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Iam\Identity\Domain\Identity;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Repository\CustomerAddressesRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Web\Tests\Support\AbstractWebTestCase;

final class CheckoutControllerTest extends AbstractWebTestCase
{
    #[Test]
    public function itShowsTheAddressForm(): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->registerCustomer('buyer-1@example.com'));

        // When
        $client->request('GET', '/checkout/address');

        // Then
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="checkout-addresses-form"]');
    }

    #[Test]
    public function itSetsBothAddressesAndRedirectsToTheDefaultReturnRoute(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->registerCustomer('buyer-2@example.com');
        $this->loginAs($client, $identity);

        // When
        $this->submitAddresses($client, '/checkout/address');

        // Then
        self::assertResponseRedirects('/sales/orders/place');
        $customerAddresses = $this->service(CustomerAddressesRepositoryInterface::class)->load(CustomerId::fromString($identity->id()->toString()));
        self::assertSame('12 rue des Lilas', $customerAddresses->shippingAddress()?->address->street);
        self::assertSame('8 avenue Foch', $customerAddresses->billingAddress()?->address->street);
    }

    #[Test]
    public function itCopiesTheShippingAddressToBillingWhenSameAsShippingIsChecked(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->registerCustomer('buyer-3@example.com');
        $this->loginAs($client, $identity);

        // When
        $this->submitAddresses($client, '/checkout/address', sameAsShipping: true);

        // Then
        $customerAddresses = $this->service(CustomerAddressesRepositoryInterface::class)->load(CustomerId::fromString($identity->id()->toString()));
        self::assertSame('12 rue des Lilas', $customerAddresses->billingAddress()?->address->street);
    }

    #[Test]
    public function itIgnoresAnUnknownReturnToRoute(): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->registerCustomer('buyer-4@example.com'));

        // When
        $this->submitAddresses($client, '/checkout/address?return_to=security_login');

        // Then
        self::assertResponseRedirects('/sales/orders/place');
    }

    #[Test]
    public function itRefusesAnonymousAccess(): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', '/checkout/address');

        // Then
        self::assertResponseRedirects('/login');
    }

    private function submitAddresses(KernelBrowser $client, string $uri, bool $sameAsShipping = false): void
    {
        $crawler = $client->request('GET', $uri);
        $form = $crawler->filter('[data-testid="checkout-addresses-form"]')->form();
        $prefix = $form->getName();

        $values = [
            \sprintf('%s[shipping][fullName][firstName]', $prefix) => 'Ada',
            \sprintf('%s[shipping][fullName][lastName]', $prefix) => 'Lovelace',
            \sprintf('%s[shipping][address][street]', $prefix) => '12 rue des Lilas',
            \sprintf('%s[shipping][address][postalCode]', $prefix) => '75001',
            \sprintf('%s[shipping][address][city]', $prefix) => 'Paris',
            \sprintf('%s[billing][fullName][firstName]', $prefix) => 'Ada',
            \sprintf('%s[billing][fullName][lastName]', $prefix) => 'Lovelace',
            \sprintf('%s[billing][address][street]', $prefix) => '8 avenue Foch',
            \sprintf('%s[billing][address][postalCode]', $prefix) => '75116',
            \sprintf('%s[billing][address][city]', $prefix) => 'Paris',
        ];

        if ($sameAsShipping) {
            $values[\sprintf('%s[sameAsShipping]', $prefix)] = '1';
        }

        $form->setValues($values);

        $client->submit($form);
    }

    private function registerCustomer(string $email): Identity
    {
        $identity = IdentityTestFactory::new()->store();
        CustomerTestFactory::new()->withId($identity->id()->toString())->withEmail($email)->store();

        return $identity;
    }
}
