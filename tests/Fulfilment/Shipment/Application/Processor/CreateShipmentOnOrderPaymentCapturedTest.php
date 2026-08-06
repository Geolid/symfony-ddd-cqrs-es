<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Processor\CreateShipmentOnOrderPaymentCaptured;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Event\OrderPaymentCapturedIntegrationEvent;
use Sales\Order\Domain\Order;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CreateShipmentOnOrderPaymentCapturedTest extends AbstractIntegrationTestCase
{
    private CreateShipmentOnOrderPaymentCaptured $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(CreateShipmentOnOrderPaymentCaptured::class);
    }

    #[Test]
    public function itOpensAShipmentOnOrderPaymentCaptured(): void
    {
        // Given
        $order = $this->placedOrder();

        // When
        ($this->processor)($this->orderPaymentCaptured($order));

        // Then
        $results = array_values(iterator_to_array($this->service(ShipmentFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame(ShipmentId::forOrder($order->id()->toString())->toString(), $results[0]->id);
        self::assertSame($order->id()->toString(), $results[0]->orderId);
        self::assertSame('pending', $results[0]->status);
        self::assertSame('buyer@example.com', $this->addressOf($order));
    }

    #[Test]
    public function itOpensASingleShipmentWhenReplayedOnOrderPaymentCaptured(): void
    {
        // Given
        $order = $this->placedOrder();
        ($this->processor)($this->orderPaymentCaptured($order));

        // When
        ($this->processor)($this->orderPaymentCaptured($order));

        // Then
        self::assertCount(1, iterator_to_array($this->service(ShipmentFinderInterface::class)));
    }

    private function placedOrder(): Order
    {
        $order = OrderTestFactory::new()
            ->withCustomerId(Uuid::uuid7()->toString())
            ->withBuyerAddress('buyer@example.com')
            ->withTotalAmountInCents(4_200)
            ->create();

        $this->store($order);

        return $order;
    }

    private function orderPaymentCaptured(Order $order): OrderPaymentCapturedIntegrationEvent
    {
        return new OrderPaymentCapturedIntegrationEvent(
            orderId: $order->id()->toString(),
            customerId: Uuid::uuid7()->toString(),
            buyerAddress: 'buyer@example.com',
            capturedAt: '2026-01-01T00:00:00+00:00',
        );
    }

    private function addressOf(Order $order): ?string
    {
        return $this->service(ShipmentRepositoryInterface::class)
            ->load(ShipmentId::forOrder($order->id()->toString()))
            ->customerAddress();
    }
}
