<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\MarkShipmentDelivered;

use Fulfilment\Shipment\Application\Command\MarkShipmentDelivered\MarkShipmentDelivered;
use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Exception\AggregateNotFoundException;
use Support\AbstractIntegrationTestCase;

final class MarkShipmentDeliveredHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itMarksADispatchedShipmentAsDelivered(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->dispatched()->store();

        // When
        $this->dispatch(new MarkShipmentDelivered($shipment->id()->toString()));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentStatus::DELIVERED, $results[0]->status);
        self::assertNotNull($results[0]->deliveredAt);
    }

    #[Test]
    public function itFailsWhenTheShipmentHasNotLeftYet(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->store();

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new MarkShipmentDelivered($shipment->id()->toString()));
    }

    #[Test]
    public function itFailsWhenTheShipmentDoesNotExist(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(AggregateNotFoundException::class);

        // When
        $this->dispatch(new MarkShipmentDelivered($id));
    }
}
