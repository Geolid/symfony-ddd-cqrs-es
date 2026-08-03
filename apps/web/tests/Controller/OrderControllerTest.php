<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Web\Tests\Support\AbstractWebTestCase;

final class OrderControllerTest extends AbstractWebTestCase
{
    #[Test]
    public function itPlacesAnOrderAndShowsItsDetail(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);

        // When
        $id = $this->placeOrder($client);

        // Then
        self::assertResponseRedirects(\sprintf('/sales/orders/%s', $id));
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="order-total"]', '17.50');
        self::assertSelectorExists('[data-testid="flash-success"]');
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
    public function itPaysForAnOrder(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);
        $id = $this->placeOrder($client);
        self::getContainer()->set('globex.client', new MockHttpClient(new MockResponse(
            json_encode(['chargeReference' => 'GLBX-TEST-REF'], \JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']],
        )));

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/pay', $id), [
            '_token' => $this->csrfToken($client, 'pay-order-'.$id),
        ]);

        // Then
        self::assertResponseRedirects(\sprintf('/sales/orders/%s', $id));
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="flash-success"]');
        self::assertSelectorTextContains('[data-testid="order-payment-reference"]', 'GLBX-TEST-REF');

        $payment = $this->service(OrderPaymentFinderInterface::class)->ofOrder($id);
        self::assertNotNull($payment);
        self::assertSame('GLBX-TEST-REF', $payment->reference);
    }

    #[Test]
    public function itRefusesToPayTwice(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);
        $id = $this->placeOrder($client);
        $this->store(OrderPaymentTestFactory::new()->withOrderId($id)->create());

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/pay', $id), [
            '_token' => $this->csrfToken($client, 'pay-order-'.$id),
        ]);

        // Then
        self::assertResponseRedirects(\sprintf('/sales/orders/%s', $id));
        $client->followRedirect();
        self::assertSelectorExists('[data-testid="flash-error"]');
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
    public function itRefusesToCancelAnOrderAlreadyPaidFor(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);
        $id = $this->placeOrder($client);
        $this->store(OrderPaymentTestFactory::new()->withOrderId($id)->create());

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
