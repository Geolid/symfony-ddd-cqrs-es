<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Catalog\Product\Application\Command\RepriceProduct\RepriceProduct;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Order\Application\Command\DeliverOrder\DeliverOrder;
use Sales\Order\Application\Command\DispatchOrder\DispatchOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Application\Command\CommandBusInterface;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Lock\LockFactory;
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
        $identity = $this->createCustomer('buyer-locale@example.com');
        $this->loginAs($client, $identity);
        $order = OrderTestFactory::new()->withCustomerId($identity->id->toString())->withTotalAmountInCents(1_750)->create();
        $this->store($order);

        // When
        $client->request('GET', $path);

        // Then
        self::assertResponseIsSuccessful();
        self::assertSame($locale, $client->getRequest()->getLocale());
        self::assertSelectorTextContains('[data-testid="order-total"]', '17.50');
        self::assertSelectorExists('[data-status="placed"]');
    }

    #[Test]
    public function itRefusesAnonymousAccess(): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', $this->path('sales_order_list'));

        // Then
        self::assertResponseRedirects($this->path('security_login'));
    }

    #[Test]
    #[DataProvider('provideLocalizedOrdersPath')]
    public function itShowsOrderDetail(string $locale, string $path): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-locale-show@example.com');
        $this->loginAs($client, $identity);
        $order = OrderTestFactory::new()->withCustomerId($identity->id->toString())->withTotalAmountInCents(1_750)->create();
        $this->store($order);

        // When
        $client->request('GET', \sprintf('%s/%s', $path, $order->id->toString()));

        // Then
        self::assertResponseIsSuccessful();
        self::assertSame($locale, $client->getRequest()->getLocale());
        self::assertSelectorTextContains('[data-testid="order-line"]', 'Assorted goods');
        self::assertSelectorTextContains('[data-testid="order-line"]', 'x1');
        self::assertSelectorTextContains('[data-testid="order-total"]', '17.50');
        self::assertSelectorExists('[data-testid="pay-button"]');
    }

    #[Test]
    public function itRefusesToShowAnotherCustomersOrder(): void
    {
        // Given
        $client = self::browser();
        $owner = $this->createCustomer('owner@example.com');
        $order = OrderTestFactory::new()->withCustomerId($owner->id->toString())->create();
        $this->store($order);
        $this->loginAs($client, $this->createCustomer('intruder@example.com'));

        // When
        $client->request('GET', $this->path('sales_order_show', ['id' => $order->id->toString()]));

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
        $this->loginAs($client, $this->createCustomer('buyer-locale-place@example.com'));

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
        $this->loginAs($client, $this->createCustomer('buyer-1@example.com'));
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($product);

        // When
        $crawler = $client->request('GET', $this->path('sales_order_place'));
        $form = $crawler->filter('[data-testid="place-order-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[lines][0][productId]', $prefix) => $product->id->toString(),
            \sprintf('%s[lines][0][quantity]', $prefix) => '1',
        ]);
        $client->submit($form);

        // Then
        self::assertResponseRedirects();
        $location = (string) $client->getResponse()->headers->get('Location');
        self::assertMatchesRegularExpression('#^/sales/orders/[0-9a-f-]{36}$#', $location);

        $order = $this->service(OrderFinderInterface::class)->ofId(basename($location));
        self::assertSame(1_750, $order->totalAmountInCents);
        self::assertSame(OrderStatus::PLACED, $order->status);
    }

    #[Test]
    public function itRedirectsToCheckoutAddressFormWhenAddressesAreIncomplete(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $customer = CustomerTestFactory::new()->withId($identity->id->toString())->withEmail('buyer-9@example.com')->create();
        $this->store($identity, $customer);
        $this->loginAs($client, $identity);
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($product);

        // When
        $crawler = $client->request('GET', $this->path('sales_order_place'));
        $form = $crawler->filter('[data-testid="place-order-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[lines][0][productId]', $prefix) => $product->id->toString(),
            \sprintf('%s[lines][0][quantity]', $prefix) => '1',
        ]);
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('checkout_address_complete', ['return_to' => 'sales_order_place']));
        self::assertCount(0, $this->service(OrderFinderInterface::class)->byCustomer($identity->id->toString()));
    }

    #[Test]
    public function itFlagsAnOutdatedCatalogWhenThePriceChanged(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-11@example.com');
        $this->loginAs($client, $identity);
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($product);
        $crawler = $client->request('GET', $this->path('sales_order_place'));
        $form = $crawler->filter('[data-testid="place-order-form"]')->form();
        $prefix = $form->getName();
        $form->setValues([
            \sprintf('%s[lines][0][productId]', $prefix) => $product->id->toString(),
            \sprintf('%s[lines][0][quantity]', $prefix) => '1',
        ]);
        $this->service(CommandBusInterface::class)->dispatch(new RepriceProduct($product->id->toString(), 2_000));

        // When
        $client->submit($form);

        // Then
        self::assertResponseRedirects($this->path('sales_order_place'));
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="flash-error"]', 'sales.order.flash.catalog_changed');
        self::assertCount(0, $this->service(OrderFinderInterface::class)->byCustomer($identity->id->toString()));
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
        self::getContainer()->set('globex.client', new MockHttpClient(new MockResponse(
            json_encode(['chargeReference' => 'GLBX-TEST-REF', 'checkoutUrl' => self::CHECKOUT_URL], \JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']],
        )));
        $identity = $this->createCustomer('buyer-3@example.com');
        $this->loginAs($client, $identity);
        $order = OrderTestFactory::new()->withCustomerId($identity->id->toString())->create();
        $this->store($order);
        $id = $order->id->toString();

        // When
        $client->request('GET', $this->path('sales_order_pay', ['id' => $id]));

        // Then
        self::assertResponseRedirects(self::CHECKOUT_URL);
        $orderPayment = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-TEST-REF');
        self::assertSame($id, $orderPayment->orderId);
        self::assertSame(self::CHECKOUT_URL, $orderPayment->checkoutUrl);
        self::assertSame(OrderPaymentStatus::REQUESTED, $orderPayment->status);
    }

    #[Test]
    public function itResumesAnAlreadyRequestedPayment(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-8@example.com');
        $this->loginAs($client, $identity);
        $order = OrderTestFactory::new()->withCustomerId($identity->id->toString())->create();
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->withReference('GLBX-EXISTING-REF')
            ->withCheckoutUrl('https://checkout.test/session/already-requested')
            ->create();
        $this->store($order, $orderPayment);

        // When
        $client->request('GET', $this->path('sales_order_pay', ['id' => $order->id->toString()]));

        // Then
        self::assertResponseRedirects('https://checkout.test/session/already-requested');
        $orderPayment = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-EXISTING-REF');
        self::assertSame('https://checkout.test/session/already-requested', $orderPayment->checkoutUrl);
    }

    #[Test]
    public function itShowsAPaymentInProgressPageWhenARequestIsAlreadyInFlight(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-10@example.com');
        $this->loginAs($client, $identity);
        $order = OrderTestFactory::new()->withCustomerId($identity->id->toString())->create();
        $this->store($order);
        $id = $order->id->toString();
        $lock = $this->service(LockFactory::class)->createLock(\sprintf('sales.order.payment_request.%s', $id));
        $lock->acquire();

        try {
            // When
            $client->request('GET', $this->path('sales_order_pay', ['id' => $id]));

            // Then
            self::assertResponseStatusCodeSame(409);
            self::assertSelectorTextContains('[data-testid="payment-in-progress-message"]', 'sales.order.payment_in_progress.message');
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function itRefusesToPayForACancelledOrder(): void
    {
        // Given
        $client = self::browser();
        $identity = IdentityTestFactory::new()->create();
        $customer = CustomerTestFactory::new()->withId($identity->id->toString())->withEmail('buyer-7@example.com')->create();
        $order = OrderTestFactory::new()->withCustomerId($customer->id->toString())->cancelled()->create();
        $this->store($identity, $customer, $order);
        $this->loginAs($client, $identity);

        // When
        $client->request('GET', $this->path('sales_order_pay', ['id' => $order->id->toString()]));

        // Then
        self::assertResponseRedirects($this->path('sales_order_show', ['id' => $order->id->toString()]));
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="flash-error"]', 'sales.order.flash.cannot_pay_cancelled');
    }

    #[Test]
    public function itCancelsAPlacedOrder(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-4@example.com');
        $this->loginAs($client, $identity);
        $order = OrderTestFactory::new()->withCustomerId($identity->id->toString())->create();
        $this->store($order);
        $id = $order->id->toString();

        // When
        $client->request('POST', $this->path('sales_order_cancel', ['id' => $id]), [
            '_token' => $this->csrfToken($client, 'cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseRedirects($this->path('sales_order_show', ['id' => $id]));
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="flash-success"]', 'sales.order.flash.cancelled');

        $order = $this->service(OrderFinderInterface::class)->ofId($id);
        self::assertSame(OrderStatus::CANCELLED, $order->status);
    }

    #[Test]
    public function itRefusesToCancelADispatchedOrder(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-5@example.com');
        $this->loginAs($client, $identity);
        $order = OrderTestFactory::new()->withCustomerId($identity->id->toString())->create();
        $this->store($order);
        $id = $order->id->toString();
        $commandBus = $this->service(CommandBusInterface::class);
        $commandBus->dispatch(new ConfirmOrder($id));
        $commandBus->dispatch(new DispatchOrder($id));

        // When
        $client->request('POST', $this->path('sales_order_cancel', ['id' => $id]), [
            '_token' => $this->csrfToken($client, 'cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseRedirects($this->path('sales_order_show', ['id' => $id]));
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="flash-error"]', 'sales.order.flash.not_cancellable');

        $order = $this->service(OrderFinderInterface::class)->ofId($id);
        self::assertSame(OrderStatus::DISPATCHED, $order->status);
    }

    #[Test]
    public function itRefusesToCancelWithAnInvalidCsrfToken(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-6@example.com');
        $this->loginAs($client, $identity);
        $order = OrderTestFactory::new()->withCustomerId($identity->id->toString())->create();
        $this->store($order);
        $id = $order->id->toString();

        // When
        $client->request('POST', $this->path('sales_order_cancel', ['id' => $id]), ['_token' => 'invalid']);

        // Then
        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function itRefusesToCancelAnotherCustomersOrder(): void
    {
        // Given
        $client = self::browser();
        $owner = $this->createCustomer('owner-cancel@example.com');
        $order = OrderTestFactory::new()->withCustomerId($owner->id->toString())->create();
        $this->store($order);
        $id = $order->id->toString();
        $this->loginAs($client, $this->createCustomer('intruder-cancel@example.com'));

        // When
        $client->request('POST', $this->path('sales_order_cancel', ['id' => $id]), [
            '_token' => $this->csrfToken($client, 'cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function itRequestsAReturnForADeliveredOrder(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-11@example.com');
        $this->loginAs($client, $identity);
        $order = OrderTestFactory::new()->withCustomerId($identity->id->toString())->create();
        $this->store($order);
        $id = $order->id->toString();
        $commandBus = $this->service(CommandBusInterface::class);
        $commandBus->dispatch(new ConfirmOrder($id));
        $commandBus->dispatch(new DispatchOrder($id));
        $commandBus->dispatch(new DeliverOrder($id));

        // When
        $client->request('POST', $this->path('sales_order_request_return', ['id' => $id]), [
            '_token' => $this->csrfToken($client, 'request-order-return-'.$id),
        ]);

        // Then
        self::assertResponseRedirects($this->path('sales_order_show', ['id' => $id]));
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="flash-success"]', 'sales.order.flash.return_requested');

        $order = $this->service(OrderFinderInterface::class)->ofId($id);
        self::assertSame(OrderStatus::RETURN_REQUESTED, $order->status);
    }

    #[Test]
    public function itRefusesToRequestAReturnForAnOrderNotYetDelivered(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-12@example.com');
        $this->loginAs($client, $identity);
        $order = OrderTestFactory::new()->withCustomerId($identity->id->toString())->create();
        $this->store($order);
        $id = $order->id->toString();

        // When
        $client->request('POST', $this->path('sales_order_request_return', ['id' => $id]), [
            '_token' => $this->csrfToken($client, 'request-order-return-'.$id),
        ]);

        // Then
        self::assertResponseRedirects($this->path('sales_order_show', ['id' => $id]));
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="flash-error"]', 'sales.order.flash.not_returnable');

        $order = $this->service(OrderFinderInterface::class)->ofId($id);
        self::assertSame(OrderStatus::PLACED, $order->status);
    }

    #[Test]
    public function itRefusesToRequestAReturnWithAnInvalidCsrfToken(): void
    {
        // Given
        $client = self::browser();
        $identity = $this->createCustomer('buyer-13@example.com');
        $this->loginAs($client, $identity);
        $order = OrderTestFactory::new()->withCustomerId($identity->id->toString())->create();
        $this->store($order);
        $id = $order->id->toString();

        // When
        $client->request('POST', $this->path('sales_order_request_return', ['id' => $id]), ['_token' => 'invalid']);

        // Then
        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function itRefusesToRequestAReturnForAnotherCustomersOrder(): void
    {
        // Given
        $client = self::browser();
        $owner = $this->createCustomer('owner-request-return@example.com');
        $order = OrderTestFactory::new()->withCustomerId($owner->id->toString())->create();
        $this->store($order);
        $id = $order->id->toString();
        $this->loginAs($client, $this->createCustomer('intruder-request-return@example.com'));

        // When
        $client->request('POST', $this->path('sales_order_request_return', ['id' => $id]), [
            '_token' => $this->csrfToken($client, 'request-order-return-'.$id),
        ]);

        // Then
        self::assertResponseStatusCodeSame(403);
    }

    private function createCustomer(string $email): Identity
    {
        $identity = IdentityTestFactory::new()->create();
        $customer = CustomerTestFactory::new()
            ->withId($identity->id->toString())
            ->withEmail($email)
            ->withShippingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris', 'FR')))
            ->withBillingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris', 'FR')))
            ->create();
        $this->store($identity, $customer);

        return $identity;
    }
}
