<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
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
        $crawler = $client->request('GET', '/sales/orders/place');
        $form = $crawler->filter('form')->form();
        $prefix = $form->getName();

        $form->setValues([
            \sprintf('%s[lines][0][label]', $prefix) => 'Espresso cups, set of 6',
            \sprintf('%s[lines][0][quantity]', $prefix) => '1',
            \sprintf('%s[lines][0][unitAmountInCents]', $prefix) => '17.50',
        ]);

        $client->submit($form);

        $orders = iterator_to_array($this->service(OrderFinderInterface::class)->paginate(1, 20));

        return $orders[0]->id;
    }
}
