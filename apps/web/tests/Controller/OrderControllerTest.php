<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\CaptureOrderPayment\CaptureOrderPayment;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Shared\Application\Command\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Web\Tests\Support\AbstractWebTestCase;

final class OrderControllerTest extends AbstractWebTestCase
{
    private const string CHECKOUT_URL = 'https://checkout.test/session/GLBX-TEST-REF';

    #[Test]
    public function itPlacesAnOrderAndRedirectsToCheckout(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);

        // When
        $this->placeOrder($client);

        // Then
        self::assertResponseRedirects(self::CHECKOUT_URL);
    }

    #[Test]
    public function itShowsThePlacedOrderInTheList(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);
        $this->placeOrder($client);

        // When
        $client->request('GET', '/sales/orders');

        // Then
        self::assertSelectorTextContains('[data-testid="order-total"]', '17.50');
    }

    #[Test]
    public function itResumesPaymentForAPendingOrder(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);
        $id = $this->placeOrder($client);

        // When
        $client->request('GET', \sprintf('/sales/orders/%s/checkout', $id));

        // Then
        self::assertResponseRedirects(self::CHECKOUT_URL);
    }

    #[Test]
    public function itRefusesAnonymousAccess(): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', '/sales/orders');

        // Then
        self::assertResponseRedirects('/login');
    }

    #[Test]
    public function itCancelsAPlacedOrder(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);
        $id = $this->placeOrder($client);

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/cancel', $id), [
            '_token' => $this->csrfToken($client, 'cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseRedirects(\sprintf('/sales/orders/%s', $id));
    }

    #[Test]
    public function itRefusesToCancelAnOrderAlreadyCaptured(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);
        $id = $this->placeOrder($client);
        $payment = $this->service(OrderPaymentFinderInterface::class)->ofOrder($id);
        self::assertNotNull($payment);
        $this->service(CommandBusInterface::class)->dispatch(new CaptureOrderPayment($payment->id));

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/cancel', $id), [
            '_token' => $this->csrfToken($client, 'cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseRedirects(\sprintf('/sales/orders/%s', $id));
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="flash-error"]');

        $order = $this->service(OrderFinderInterface::class)->ofId($id);
        self::assertNotNull($order);
        self::assertSame('placed', $order->status);
    }

    #[Test]
    public function itRefusesToCancelWithAnInvalidCsrfToken(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);
        $id = $this->placeOrder($client);

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
        $this->loggedInCustomer($client, 'owner@example.com');
        $id = $this->placeOrder($client);

        $client->request('GET', '/logout');
        $this->loggedInCustomer($client, 'intruder@example.com');

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/cancel', $id), [
            '_token' => $this->csrfToken($client, 'cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function itRefusesToShowAnotherCustomersOrder(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client, 'owner@example.com');
        $id = $this->placeOrder($client);

        $client->request('GET', '/logout');
        $this->loggedInCustomer($client, 'intruder@example.com');

        // When
        $client->request('GET', \sprintf('/sales/orders/%s', $id));

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

    private function placeOrder(KernelBrowser $client): string
    {
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($product);

        $crawler = $client->request('GET', '/sales/orders/place');
        $form = $crawler->filter('main form')->form();
        $prefix = $form->getName();

        $form->setValues([
            \sprintf('%s[lines][0][productId]', $prefix) => $product->id()->toString(),
            \sprintf('%s[lines][0][quantity]', $prefix) => '1',
        ]);

        $client->submit($form);

        $orders = iterator_to_array($this->service(OrderFinderInterface::class)->paginate(1, 20));

        return $orders[0]->id;
    }
}
