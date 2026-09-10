<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Command\RequestShipment;

use Fulfilment\Shipping\Application\Command\RequestShipment\RequestShipment;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestShipmentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $orderId = ShipmentBuilder::sample('orderId');
        $buyerId = ShipmentBuilder::sample('buyerId');
        $originData = PostalAddressMapper::toArray(ShipmentBuilder::sample('origin'));
        $destinationData = PostalAddressMapper::toArray(ShipmentBuilder::sample('destination'));

        // When
        $this->dispatch(new RequestShipment($id, $orderId, $buyerId, $originData, $destinationData));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($orderId, $result->orderId);
        self::assertSame(ShipmentStatus::REQUESTED, $result->status);
        self::assertSame($destinationData['recipientName'], $result->destination->recipientName);
        self::assertSame($destinationData['address'], (array) $result->destination->address);
    }
}
