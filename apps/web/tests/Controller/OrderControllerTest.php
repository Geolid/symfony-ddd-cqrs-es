<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Ordering\Order\Application\Finder\Order\OrderFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Web\Tests\Support\AbstractWebTestCase;

final class OrderControllerTest extends AbstractWebTestCase
{
    #[Test]
    public function itPlacesAnOrderAndShowsItInTheList(): void
    {
        // Given
        $client = self::browser();

        // When
        $this->placeOrder($client, 'customer-1', 4_200);

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
        $id = $this->placeOrder($client, 'customer-2', 1_000);

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
        $id = $this->placeOrder($client, 'customer-3', 1_000);

        // When
        $client->request('POST', \sprintf('/orders/%s/cancel', $id), ['_token' => 'invalid']);

        // Then
        self::assertResponseStatusCodeSame(400);
    }

    private function placeOrder(KernelBrowser $client, string $customerId, int $totalAmountInCents): string
    {
        $crawler = $client->request('GET', '/orders/new');
        $form = $crawler->filter('form')->form();
        $prefix = $form->getName();

        $form->setValues([
            \sprintf('%s[customerId]', $prefix) => $customerId,
            \sprintf('%s[totalAmountInCents]', $prefix) => (string) $totalAmountInCents,
        ]);

        $client->submit($form);

        return iterator_to_array($this->service(OrderFinderInterface::class)->withCustomer($customerId))[0]->id;
    }
}
