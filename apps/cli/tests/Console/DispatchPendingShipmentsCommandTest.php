<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Domain\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;

final class DispatchPendingShipmentsCommandTest extends AbstractCliTestCase
{
    #[Test]
    public function itDispatchesEveryPendingShipment(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->create();
        $this->store($shipment);

        // When
        $tester = $this->tester('fulfilment:shipment:dispatch-pending');
        $tester->execute([]);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('1 shipment(s) dispatched.', $tester->getDisplay());

        $results = iterator_to_array($this->service(ShipmentFinderInterface::class)->withStatus(ShipmentStatus::DISPATCHED));
        self::assertCount(1, $results);
    }
}
