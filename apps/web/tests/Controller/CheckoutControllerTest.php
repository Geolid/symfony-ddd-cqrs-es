<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Iam\Identity\Domain\Identity;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Order\Application\Finder\Buyer\PostalAddressResult;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Web\Tests\Support\AbstractWebTestCase;

final class CheckoutControllerTest extends AbstractWebTestCase
{
    #[Test]
    #[DataProvider('provideLocalizedPath')]
    public function itShowsAddress(string $locale, string $path): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->createCustomer('buyer-1@example.com'));

        // When
        $client->request('GET', $path);

        // Then
        self::assertResponseIsSuccessful();
        $request = $client->getRequest();
        $requestLocale = $request->getLocale();
        self::assertSame($locale, $requestLocale);
        self::assertSelectorExists('[data-testid="checkout-addresses-form"]');
    }

    #[Test]
    public function itRegistersAddressesAndRedirectsToTheDefaultReturnRoute(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-2@example.com');
        $this->loginAs($client, $identity);

        // When
        $this->submitAddresses($client, $this->path('checkout_address_complete'));

        // Then
        self::assertResponseRedirects($this->path('sales_order_place'));
        $buyer = $this->service(BuyerFinderInterface::class)->ofIdOrNull($identity->id->toString());
        self::assertNotNull($buyer);
        self::assertNotNull($buyer->shippingAddress);
        self::assertSame(
            ['recipientName' => 'Ada Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR'],
            $this->postalAddress($buyer->shippingAddress),
        );
        self::assertNotNull($buyer->billingAddress);
        self::assertSame(
            ['recipientName' => 'Ada Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris', 'countryCode' => 'FR'],
            $this->postalAddress($buyer->billingAddress),
        );
    }

    #[Test]
    public function itUsesTheSameAddressForBilling(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-3@example.com');
        $this->loginAs($client, $identity);

        // When
        $this->submitAddresses($client, $this->path('checkout_address_complete'), sameAsShipping: true);

        // Then
        $buyer = $this->service(BuyerFinderInterface::class)->ofIdOrNull($identity->id->toString());
        self::assertNotNull($buyer);
        self::assertNotNull($buyer->shippingAddress);
        self::assertSame(
            ['recipientName' => 'Ada Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR'],
            $this->postalAddress($buyer->shippingAddress),
        );
        self::assertNotNull($buyer->billingAddress);
        self::assertSame(
            ['recipientName' => 'Ada Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR'],
            $this->postalAddress($buyer->billingAddress),
        );
    }

    #[Test]
    public function itRegistersAddressesAndRedirectsToTheExplicitReturnRoute(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-4@example.com');
        $this->loginAs($client, $identity);

        // When
        $this->submitAddresses($client, $this->path('checkout_address_complete', ['return_to' => 'sales_order_place']));

        // Then
        self::assertResponseRedirects($this->path('sales_order_place'));
        $buyer = $this->service(BuyerFinderInterface::class)->ofIdOrNull($identity->id->toString());
        self::assertNotNull($buyer);
        self::assertNotNull($buyer->shippingAddress);
        self::assertSame(
            ['recipientName' => 'Ada Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR'],
            $this->postalAddress($buyer->shippingAddress),
        );
        self::assertNotNull($buyer->billingAddress);
        self::assertSame(
            ['recipientName' => 'Ada Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris', 'countryCode' => 'FR'],
            $this->postalAddress($buyer->billingAddress),
        );
    }

    #[Test]
    public function itRefusesAnUnknownReturnToRoute(): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->createCustomer('buyer-5@example.com'));

        // When
        $client->request('GET', $this->path('checkout_address_complete', ['return_to' => 'security_login']));

        // Then
        self::assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function itRefusesAnonymousAccess(): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', $this->path('checkout_address_complete'));

        // Then
        self::assertResponseRedirects($this->path('security_login'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideLocalizedPath(): iterable
    {
        yield 'en' => ['en', '/checkout/address'];
        yield 'fr' => ['fr', '/finalisation/adresse'];
    }

    private function submitAddresses(KernelBrowser $client, string $uri, bool $sameAsShipping = false): void
    {
        $crawler = $client->request('GET', $uri);
        $form = $crawler->filter('[data-testid="checkout-addresses-form"]')->form();
        $prefix = $form->getName();

        $values = [
            \sprintf('%s[shipping][recipientName]', $prefix) => 'Ada Lovelace',
            \sprintf('%s[shipping][address][street]', $prefix) => '12 rue des Lilas',
            \sprintf('%s[shipping][address][postalCode]', $prefix) => '75001',
            \sprintf('%s[shipping][address][city]', $prefix) => 'Paris',
            \sprintf('%s[shipping][address][countryCode]', $prefix) => 'FR',
            \sprintf('%s[billing][recipientName]', $prefix) => 'Ada Lovelace',
            \sprintf('%s[billing][address][street]', $prefix) => '8 avenue Foch',
            \sprintf('%s[billing][address][postalCode]', $prefix) => '75116',
            \sprintf('%s[billing][address][city]', $prefix) => 'Paris',
            \sprintf('%s[billing][address][countryCode]', $prefix) => 'FR',
        ];

        if ($sameAsShipping) {
            $values[\sprintf('%s[sameAsShipping]', $prefix)] = '1';
        }

        $form->setValues($values);

        $client->submit($form);
    }

    private function createCustomer(string $email): Identity
    {
        $identity = IdentityBuilder::new()->create();
        $customer = CustomerBuilder::new()->withId($identity->id->toString())->withEmail($email)->create();
        $this->store($identity, $customer);

        return $identity;
    }

    /**
     * @return array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function postalAddress(PostalAddressResult $address): array
    {
        return [
            'recipientName' => $address->recipientName,
            'street' => $address->street,
            'postalCode' => $address->postalCode,
            'city' => $address->city,
            'countryCode' => $address->countryCode,
        ];
    }
}
