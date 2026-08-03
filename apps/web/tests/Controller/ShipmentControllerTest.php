<?php

declare(strict_types=1);

namespace Web\Tests\Controller;

use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Web\Tests\Support\AbstractWebTestCase;

final class ShipmentControllerTest extends AbstractWebTestCase
{
    #[Test]
    public function itShowsEmptyState(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);

        // When
        $client->request('GET', '/fulfilment/shipments');

        // Then
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="shipments-empty"]');
    }

    #[Test]
    public function itRefusesAnonymousAccess(): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', '/fulfilment/shipments');

        // Then
        self::assertResponseRedirects('/login');
    }

    #[Test]
    public function itShowsShipmentsFilteredByStatus(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);

        // When
        $client->request('GET', '/fulfilment/shipments?status=pending&page=1&itemsPerPage=5');

        // Then
        self::assertResponseIsSuccessful();
    }

    #[Test]
    public function itShowsTheOrderTotalInUnitsAndTheCarrierReference(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);
        $order = OrderTestFactory::new()->withTotalAmountInCents(2_500)->create();
        $this->store($order);
        $this->store(
            ShipmentTestFactory::new()->withOrderId($order->id()->toString())->tracked('ACME-4Q7X2K9')->create(),
        );

        // When
        $client->request('GET', '/fulfilment/shipments');

        // Then
        self::assertSelectorTextSame('[data-testid="shipment-total"]', '25.00');
        self::assertSelectorTextSame('[data-testid="shipment-tracking-reference"]', 'ACME-4Q7X2K9');
    }

    #[Test]
    public function itShowsADashWhereAShipmentHasNothingToShow(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);
        $this->store(ShipmentTestFactory::new()->create());

        // When
        $client->request('GET', '/fulfilment/shipments');

        // Then
        self::assertSelectorTextSame('[data-testid="shipment-total"]', '—');
        self::assertSelectorTextSame('[data-testid="shipment-tracking-reference"]', '—');
    }

    #[Test]
    public function itRefusesAnUnknownStatus(): void
    {
        // Given
        $client = self::browser();
        $this->loggedInCustomer($client);

        // When
        $client->request('GET', '/fulfilment/shipments?status=teleported');

        // Then
        self::assertResponseStatusCodeSame(422);
    }
}
