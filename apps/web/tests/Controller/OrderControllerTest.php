<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Web\Tests\Support\AbstractWebTestCase;

final class OrderControllerTest extends AbstractWebTestCase
{
    #[Test]
    public function itPlacesAnOrderAndShowsItInTheList(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);

        // When
        $this->placeOrder($client);

        // Then
        self::assertResponseRedirects('/sales/orders');
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="order-total"]', '17.50');
    }

    #[Test]
    public function itShowsThePaymentReferenceForAnOrderPendingCapture(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);
        $id = $this->placeOrder($client);
        $this->store(OrderPaymentTestFactory::new()->withOrderId($id)->withReference('GLBX-9F3K2M1P')->create());

        // When
        $client->request('GET', '/sales/orders');

        // Then
        self::assertSelectorTextContains('[data-testid="order-payment-reference"]', 'GLBX-9F3K2M1P');
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
        self::assertResponseRedirects('/sales/orders');
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

    private function placeOrder(KernelBrowser $client): string
    {
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($product);

        $crawler = $client->request('GET', '/sales/orders/place');
        $form = $crawler->filter('form')->form();
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
