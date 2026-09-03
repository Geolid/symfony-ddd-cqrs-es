<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;

final class InspectShipmentReturnCommandTest extends AbstractCliTestCase
{
    private ShipmentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itApprovesAReceivedReturn(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->create();
        $this->store($shipment);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:inspect-return', 'shipment-id' => $shipment->id->toString(), '--approve' => true]);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('approved', $tester->getDisplay());
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::RETURN_APPROVED, $result->status);
    }

    #[Test]
    public function itRejectsAReceivedReturn(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->create();
        $this->store($shipment);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:inspect-return', 'shipment-id' => $shipment->id->toString(), '--reject' => 'item damaged beyond resale']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('rejected', $tester->getDisplay());
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::RETURN_REJECTED, $result->status);
    }

    #[Test]
    public function itFailsWhenNeitherApproveNorRejectIsGiven(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->create();
        $this->store($shipment);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:inspect-return', 'shipment-id' => $shipment->id->toString()]);

        // Then
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    #[Test]
    public function itFailsWhenBothApproveAndRejectAreGiven(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->create();
        $this->store($shipment);
        $tester = $this->tester();

        // When
        $tester->run([
            'command' => 'fulfilment:shipment:inspect-return',
            'shipment-id' => $shipment->id->toString(),
            '--approve' => true,
            '--reject' => 'item damaged beyond resale',
        ]);

        // Then
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }
}
