<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;

final class PrepareShipmentCommandTest extends AbstractCliTestCase
{
    #[Test]
    public function itPreparesARequestedShipment(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->create();
        $other = ShipmentTestFactory::new()->create();
        $this->store($shipment, $other);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:prepare', 'shipment-id' => $shipment->id->toString()]);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('prepared', $tester->getDisplay());
        $ids = array_column(iterator_to_array($this->service(ShipmentFinderInterface::class)->byStatus(ShipmentStatus::PREPARED->value), false), 'id');
        self::assertContains($shipment->id->toString(), $ids);
        self::assertNotContains($other->id->toString(), $ids);
    }
}
