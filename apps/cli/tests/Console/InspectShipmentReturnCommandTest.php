<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
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
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->store();
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:inspect-return', 'shipment-id' => $shipment->id()->toString(), '--approve' => true]);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('approved', $tester->getDisplay());
        self::assertSame(ShipmentStatus::RETURN_APPROVED, $this->finder->ofTrackingReference('ACME-4Q7X2K9')->status);
    }

    #[Test]
    public function itRejectsAReceivedReturn(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->store();
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:inspect-return', 'shipment-id' => $shipment->id()->toString(), '--reject' => 'item damaged beyond resale']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('rejected', $tester->getDisplay());
        self::assertSame(ShipmentStatus::RETURN_REJECTED, $this->finder->ofTrackingReference('ACME-4Q7X2K9')->status);
    }

    #[Test]
    public function itFailsWhenNeitherApproveNorRejectIsGiven(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->store();
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:inspect-return', 'shipment-id' => $shipment->id()->toString()]);

        // Then
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    #[Test]
    public function itFailsWhenBothApproveAndRejectAreGiven(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->store();
        $tester = $this->tester();

        // When
        $tester->run([
            'command' => 'fulfilment:shipment:inspect-return',
            'shipment-id' => $shipment->id()->toString(),
            '--approve' => true,
            '--reject' => 'item damaged beyond resale',
        ]);

        // Then
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }
}
