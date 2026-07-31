<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
        $customerId = Uuid::uuid7()->toString();

        // When
        $this->placeOrder($client, $customerId, 4_200);

        // Then
        self::assertResponseRedirects('/sales/orders');
        $client->followRedirect();
        self::assertSelectorTextContains('[data-testid="order-customer"]', $customerId);
    }

    #[Test]
    public function itCancelsAPlacedOrder(): void
    {
        // Given
        $client = self::browser();
        $id = $this->placeOrder($client, Uuid::uuid7()->toString(), 1_000);

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
        $id = $this->placeOrder($client, Uuid::uuid7()->toString(), 1_000);

        // When
        $client->request('POST', \sprintf('/sales/orders/%s/cancel', $id), ['_token' => 'invalid']);

        // Then
        self::assertResponseStatusCodeSame(400);
    }

    private function placeOrder(KernelBrowser $client, string $customerId, int $totalAmountInCents): string
    {
        $crawler = $client->request('GET', '/sales/orders/place');
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
