<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Ordering\Order\Application\Finder\Order\OrderFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Web\Tests\Support\AbstractWebTestCase;

final class OrderControllerTest extends AbstractWebTestCase
{
    #[Test]
    public function itPlacesAnOrderAndShowsItInTheList(): void
    {
        // Given
        $client = self::browser();
        $crawler = $client->request('GET', '/orders/new');

        // When
        $form = $crawler->filter('form')->form();
        $form['customerId'] = 'customer-1';
        $form['totalAmountInCents'] = '4200';
        $client->submit($form);

        // Then
        self::assertResponseRedirects('/orders');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'customer-1');
    }

    #[Test]
    public function itCancelsAPlacedOrder(): void
    {
        // Given
        $client = self::browser();
        $crawler = $client->request('GET', '/orders/new');
        $form = $crawler->filter('form')->form();
        $form['customerId'] = 'customer-2';
        $form['totalAmountInCents'] = '1000';
        $client->submit($form);

        $id = iterator_to_array($this->service(OrderFinderInterface::class)->withCustomer('customer-2'))[0]->id;

        // When
        $client->request('POST', \sprintf('/orders/%s/cancel', $id), [
            '_token' => $this->csrfToken('cancel-order-'.$id),
        ]);

        // Then
        self::assertResponseRedirects('/orders');
    }

    #[Test]
    public function itRefusesToCancelWithAnInvalidCsrfToken(): void
    {
        // Given
        $client = self::browser();
        $crawler = $client->request('GET', '/orders/new');
        $form = $crawler->filter('form')->form();
        $form['customerId'] = 'customer-3';
        $form['totalAmountInCents'] = '1000';
        $client->submit($form);

        $id = iterator_to_array($this->service(OrderFinderInterface::class)->withCustomer('customer-3'))[0]->id;

        // When
        $client->request('POST', \sprintf('/orders/%s/cancel', $id), ['_token' => 'invalid']);

        // Then
        self::assertResponseStatusCodeSame(400);
    }
}
