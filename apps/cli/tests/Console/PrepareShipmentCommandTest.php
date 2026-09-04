<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;

final class PrepareShipmentCommandTest extends AbstractCliTestCase
{
    #[Test]
    public function itPreparesARequestedShipment(): void
    {
        // Given
        $other = ShipmentBuilder::new()->create();
        $shipment = ShipmentBuilder::new()->create();
        $this->store($other, $shipment);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:prepare', 'shipment-id' => $shipment->id->toString()]);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('prepared', $tester->getDisplay());
        $ids = array_column(iterator_to_array($this->service(ShipmentFinderInterface::class)->byStatus(ShipmentStatus::PREPARED), false), 'id');
        self::assertContains($shipment->id->toString(), $ids);
        self::assertNotContains($other->id->toString(), $ids);
    }
}
