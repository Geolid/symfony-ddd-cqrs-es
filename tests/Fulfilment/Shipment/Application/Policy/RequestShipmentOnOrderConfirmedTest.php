<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Policy\RequestShipmentOnOrderConfirmed;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Sales\Order\Domain\Order;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class RequestShipmentOnOrderConfirmedTest extends AbstractIntegrationTestCase
{
    private RequestShipmentOnOrderConfirmed $policy;
    private ShipmentFinderInterface $shipmentFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(RequestShipmentOnOrderConfirmed::class);
        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $order = $this->placedOrder();

        // When
        ($this->policy)($this->orderConfirmed($order));

        // Then
        $results = iterator_to_array($this->shipmentFinder, false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentId::forOrder($order->id->toString())->toString(), $results[0]->id);
        self::assertSame($order->id->toString(), $results[0]->orderId);
        self::assertSame(ShipmentStatus::REQUESTED, $results[0]->status);
        $shippingAddress = $this->shipmentOf($order)->shippingAddress;
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
            [
                'firstName' => $shippingAddress->fullName->firstName,
                'lastName' => $shippingAddress->fullName->lastName,
                'street' => $shippingAddress->address->street,
                'postalCode' => $shippingAddress->address->postalCode,
                'city' => $shippingAddress->address->city,
            ],
        );
    }

    #[Test]
    public function itRequestsSingleWhenReplayed(): void
    {
        // Given
        $order = $this->placedOrder();
        ($this->policy)($this->orderConfirmed($order));

        // When
        ($this->policy)($this->orderConfirmed($order));

        // Then
        self::assertCount(1, $this->shipmentFinder);
    }

    private function placedOrder(): Order
    {
        return OrderTestFactory::new()
            ->withCustomerId(Uuid::uuid7()->toString())
            ->withTotalAmountInCents(4_200)
            ->store();
    }

    private function orderConfirmed(Order $order): OrderConfirmedIntegrationEvent
    {
        return new OrderConfirmedIntegrationEvent(
            orderId: $order->id->toString(),
            customerId: Uuid::uuid7()->toString(),
            shippingAddress: ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
            confirmedAt: '2026-01-01T00:00:00+00:00',
        );
    }

    private function shipmentOf(Order $order): Shipment
    {
        return $this->service(ShipmentRepositoryInterface::class)
            ->load(ShipmentId::forOrder($order->id->toString()));
    }
}
