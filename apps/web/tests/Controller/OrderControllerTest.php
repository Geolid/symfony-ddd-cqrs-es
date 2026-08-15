<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Order\Application\Command\DispatchOrder\DispatchOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Application\Command\CommandBusInterface;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Web\Tests\Support\AbstractWebTestCase;

final class OrderControllerTest extends AbstractWebTestCase
{
    private const string CHECKOUT_URL = 'https://checkout.test/session/GLBX-TEST-REF';

    #[Test]
    #[DataProvider('provideLocalizedOrdersPath')]
    public function itShowsOrderList(string $locale, string $path): void
    {
        // Given
        $client = self::browser();
        $identity = $this->registerCustomer('buyer-locale@example.com');
        $this->loginAs($client, $identity);
        OrderTestFactory::new()->withCustomerId($identity->id()->toString())->withTotalAmountInCents(1_750)->withoutIncrementalIds()->store();

        // When
        $client->request('GET', $path);

        // Then
        self::assertResponseIsSuccessful();
        self::assertSame($locale, $client->getRequest()->getLocale());
        self::assertSelectorTextContains('[data-testid="order-total"]', '17.50');
    }

    #[Test]
    public function itRefusesAnonymousAccessToTheList(): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', '/sales/orders');

        // Then
        self::assertResponseRedirects('/login');
    }

    #[Test]
    #[DataProvider('provideLocalizedOrdersPath')]
    public function itShowsOrderDetail(string $locale, string $path): void
    {
        // Given
        $client = self::browser();
        $identity = $this->registerCustomer('buyer-locale-show@example.com');
        $this->loginAs($client, $identity);
        $order = OrderTestFactory::new()->withCustomerId($identity->id()->toString())->withoutIncrementalIds()->store();

        // When
        $client->request('GET', \sprintf('%s/%s', $path, $order->id()->toString()));

        // Then
        self::assertResponseIsSuccessful();
        self::assertSame($locale, $client->getRequest()->getLocale());
    }

    #[Test]
    public function itRefusesToShowAnotherCustomersOrder(): void
    {
        // Given
        $client = self::browser();
        $owner = $this->registerCustomer('owner@example.com');
        $order = OrderTestFactory::new()->withCustomerId($owner->id()->toString())->withoutIncrementalIds()->store();
        $this->loginAs($client, $this->registerCustomer('intruder@example.com'));

        // When
        $client->request('GET', \sprintf('/sales/orders/%s', $order->id()->toString()));

        // Then
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideLocalizedOrdersPath(): iterable
    {
        yield 'en' => ['en', '/sales/orders'];
        yield 'fr' => ['fr', '/ventes/commandes'];
    }

    #[Test]
    #[DataProvider('provideLocalizedPlacePath')]
    public function itShowsPlaceOrder(string $locale, string $path): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->registerCustomer('buyer-locale-place@example.com'));

        // When
        $client->request('GET', $path);

        // Then
        self::assertResponseIsSuccessful();
        self::assertSame($locale, $client->getRequest()->getLocale());
        self::assertSelectorExists('[data-testid="place-order-form"]');
    }

    #[Test]
    public function itPlacesAndRedirectsToItsDetail(): void
    {
        // Given
        $client = self::browser();
        $this->loginAs($client, $this->registerCustomer('buyer-1@example.com'));
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();

        // When
        $crawler = $client->request('GET', '/sales/orders/place');
        $form = $crawler->filter('[data-testid="place-order-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[lines][0][productId]', $prefix) => $product->id()->toString(),
            \sprintf('%s[lines][0][quantity]', $prefix) => '1',
        ]);
        $client->submit($form);

        // Then
        $location = (string) $client->getResponse()->headers->get('Location');
        self::assertMatchesRegularExpression('#^/sales/orders/[0-9a-f-]{36}$#', $location);
    }

    #[Test]
    public function itRedirectsToTheCheckoutAddressFormWhenAddressesAreIncomplete(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->store();
        CustomerTestFactory::new()->withId($identity->id()->toString())->withEmail('buyer-9@example.com')->store();
        $this->loginAs($client, $identity);
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();

        // When
        $crawler = $client->request('GET', '/sales/orders/place');
        $form = $crawler->filter('[data-testid="place-order-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[lines][0][productId]', $prefix) => $product->id()->toString(),
            \sprintf('%s[lines][0][quantity]', $prefix) => '1',
        ]);
        $client->submit($form);

        // Then
        self::assertResponseRedirects('/checkout/address?return_to=sales_order_place');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideLocalizedPlacePath(): iterable
    {
        yield 'en' => ['en', '/sales/orders/place'];
        yield 'fr' => ['fr', '/ventes/commandes/commander'];
    }

    #[Test]
    public function itPaysForAPlacedOrder(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->registerCustomer('buyer-3@example.com');
        $this->loginAs($client, $identity);
        $id = OrderTestFactory::new()->withCustomerId($identity->id()->toString())->withoutIncrementalIds()->store()->id()->toString();

        // When
        $client->request('GET', \sprintf('/sales/orders/%s/checkout', $id));

        // Then
        self::assertResponseRedirects(self::CHECKOUT_URL);
    }

    #[Test]
    public function itResumesAnAlreadyRequestedPayment(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->registerCustomer('buyer-8@example.com');
        $this->loginAs($client, $identity);
        $id = OrderTestFactory::new()->withCustomerId($identity->id()->toString())->withoutIncrementalIds()->store()->id()->toString();
        $client->request('GET', \sprintf('/sales/orders/%s/checkout', $id));

        // When
        $client->request('GET', \sprintf('/sales/orders/%s/checkout', $id));

        // Then
        self::assertResponseRedirects(self::CHECKOUT_URL);
    }

    #[Test]
    public function itRefusesToPayForACancelledOrder(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->store();
        $customer = CustomerTestFactory::new()->withId($identity->id()->toString())->withEmail('buyer-7@example.com')->store();
        $order = OrderTestFactory::new()->withCustomerId($customer->id()->toString())->cancelled()->withoutIncrementalIds()->store();
        $this->loginAs($client, $identity);

        // When
        $client->request('GET', \sprintf('/sales/orders/%s/checkout', $order->id()->toString()));

        // Then
        self::assertResponseStatusCodeSame(409);
    }

    #[Test]
    public function itCancelsAPlacedOrder(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->registerCustomer('buyer-4@example.com');
        $this->loginAs($client, $identity);
        $id = OrderTestFactory::new()->withCustomerId($identity->id()->toString())->withoutIncrementalIds()->store()->id()->toString();

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/cancel', $id), [
            '_token' => $this->csrfToken($client, 'cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseRedirects(\sprintf('/sales/orders/%s', $id));
    }

    #[Test]
    public function itIgnoresCancellingAnOrderAlreadyDispatched(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->registerCustomer('buyer-5@example.com');
        $this->loginAs($client, $identity);
        $id = OrderTestFactory::new()->withCustomerId($identity->id()->toString())->withoutIncrementalIds()->store()->id()->toString();
        $commandBus = $this->service(CommandBusInterface::class);
        $commandBus->dispatch(new ConfirmOrder($id));
        $commandBus->dispatch(new DispatchOrder($id));

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/cancel', $id), [
            '_token' => $this->csrfToken($client, 'cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseRedirects(\sprintf('/sales/orders/%s', $id));

        $order = $this->service(OrderFinderInterface::class)->ofId($id);
        self::assertSame(OrderStatus::DISPATCHED, $order->status);
    }

    #[Test]
    public function itRefusesToCancelWithAnInvalidCsrfToken(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->registerCustomer('buyer-6@example.com');
        $this->loginAs($client, $identity);
        $id = OrderTestFactory::new()->withCustomerId($identity->id()->toString())->withoutIncrementalIds()->store()->id()->toString();

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/cancel', $id), ['_token' => 'invalid']);

        // Then
        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function itRefusesToCancelAnotherCustomersOrder(): void
    {
        // Given
        $client = self::browser();
        $owner = $this->registerCustomer('owner-cancel@example.com');
        $id = OrderTestFactory::new()->withCustomerId($owner->id()->toString())->withoutIncrementalIds()->store()->id()->toString();
        $this->loginAs($client, $this->registerCustomer('intruder-cancel@example.com'));

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/cancel', $id), [
            '_token' => $this->csrfToken($client, 'cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseStatusCodeSame(403);
    }

    protected static function browser(): KernelBrowser
    {
        $client = parent::browser();

        self::getContainer()->set('globex.client', new MockHttpClient(new MockResponse(
            json_encode(['chargeReference' => 'GLBX-TEST-REF', 'checkoutUrl' => self::CHECKOUT_URL], \JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']],
        )));

        return $client;
    }

    private function registerCustomer(string $email): Identity
    {
        $identity = IdentityTestFactory::new()->store();
        CustomerTestFactory::new()
            ->withId($identity->id()->toString())
            ->withEmail($email)
            ->withShippingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris')))
            ->withBillingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris')))
            ->store();

        return $identity;
    }
}
