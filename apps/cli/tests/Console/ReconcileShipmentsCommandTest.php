<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Domain\ValueObject\PostalAddress;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

final class ReconcileShipmentsCommandTest extends AbstractCliTestCase
{
    private ShipmentFinderInterface $shipmentFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itReconcilesPastTheThreshold(): void
    {
        // Given
        self::getContainer()->set('clock', new MockClock('2026-02-10T00:00:00+00:00'));
        self::getContainer()->set(CarrierGatewayInterface::class, new StubCarrierGateway(ShipmentStatus::DISPATCHED->value));
        $order = OrderTestFactory::new()->store();
        ShipmentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->prepared()
            ->manifested('ACME-STUCK9999', new \DateTimeImmutable('2026-02-07T00:00:00+00:00'))
            ->store();
        ShipmentTestFactory::new()
            ->prepared()
            ->manifested('ACME-FRESH9999', new \DateTimeImmutable('2026-02-09T12:00:00+00:00'))
            ->store();
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:reconcile']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('1 shipment(s) reconciled.', $tester->getDisplay());
        self::assertSame(ShipmentStatus::DISPATCHED, $this->shipmentFinder->ofTrackingReference('ACME-STUCK9999')->status);
        self::assertSame(ShipmentStatus::MANIFESTED, $this->shipmentFinder->ofTrackingReference('ACME-FRESH9999')->status);
    }

    #[Test]
    public function itReconcilesWhenSomeFail(): void
    {
        // Given
        self::getContainer()->set('clock', new MockClock('2026-02-10T00:00:00+00:00'));
        self::getContainer()->set(CarrierGatewayInterface::class, new StubCarrierGateway(ShipmentStatus::DISPATCHED->value, failingReference: 'ACME-UNREACHABLE'));
        ShipmentTestFactory::new()
            ->prepared()
            ->manifested('ACME-UNREACHABLE', new \DateTimeImmutable('2026-02-07T00:00:00+00:00'))
            ->store();
        ShipmentTestFactory::new()
            ->prepared()
            ->manifested('ACME-STUCK9999', new \DateTimeImmutable('2026-02-07T00:00:00+00:00'))
            ->store();
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:reconcile']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Failed to reconcile shipment', $tester->getDisplay());
        self::assertStringContainsString('1 shipment(s) reconciled.', $tester->getDisplay());
        self::assertSame(ShipmentStatus::MANIFESTED, $this->shipmentFinder->ofTrackingReference('ACME-UNREACHABLE')->status);
        self::assertSame(ShipmentStatus::DISPATCHED, $this->shipmentFinder->ofTrackingReference('ACME-STUCK9999')->status);
    }

    #[Test]
    public function itSkipsWhenAlreadyRunning(): void
    {
        // Given
        ShipmentTestFactory::new()->prepared()->manifested(manifestedAt: new \DateTimeImmutable('2010-01-01T00:00:00+00:00'))->store();
        $store = SemaphoreStore::isSupported() ? new SemaphoreStore() : new FlockStore();
        $lock = new LockFactory($store)->createLock('fulfilment:shipment:reconcile');
        $lock->acquire();
        $tester = $this->tester();

        try {
            // When
            $tester->run(['command' => 'fulfilment:shipment:reconcile']);

            // Then
            self::assertSame(Command::SUCCESS, $tester->getStatusCode());
            self::assertStringContainsString('already running in another process', $tester->getDisplay());
        } finally {
            $lock->release();
        }
    }
}

final readonly class StubCarrierGateway implements CarrierGatewayInterface
{
    public function __construct(
        private string $status,
        private ?string $failingReference = null,
    ) {
    }

    public function manifest(string $shipmentId, PostalAddress $deliveryAddress): string
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function manifestReturn(string $shipmentId, PostalAddress $pickupAddress): string
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function checkStatus(string $reference): string
    {
        if ($reference === $this->failingReference) {
            throw new \RuntimeException('Carrier unreachable.');
        }

        return $this->status;
    }
}
