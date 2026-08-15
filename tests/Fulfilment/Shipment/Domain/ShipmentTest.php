<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Domain;

use Fulfilment\Shipment\Domain\Event\ShipmentCancellationRejected;
use Fulfilment\Shipment\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Event\ShipmentManifested;
use Fulfilment\Shipment\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipment\Domain\Event\ShipmentRequested;
use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

final class ShipmentTest extends AggregateRootTestCase
{
    #[Test]
    public function itIsRequestedForAnOrder(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = ShipmentId::forOrder($orderId);
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $customerId = Uuid::uuid7()->toString();

        $this
            ->given()
            ->when(static fn () => Shipment::request($id, $orderId, $customerId, self::shippingAddress(), $createdAt))
            ->then(self::shipmentRequested($id->toString(), $orderId, $customerId, $createdAt));
    }

    #[Test]
    public function itPreparesRequested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $preparedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt))
            ->when(static fn (Shipment $shipment) => $shipment->prepare($preparedAt))
            ->then(new ShipmentPrepared($id, $preparedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotPrepareAnAlreadyPrepared(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $preparedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt),
                new ShipmentPrepared($id, $preparedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->prepare(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itManifestsPrepared(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $preparedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $manifestedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt),
                new ShipmentPrepared($id, $preparedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->manifest(TrackingReference::fromString('ACME-4Q7X2K9'), $manifestedAt))
            ->then(new ShipmentManifested($id, 'ACME-4Q7X2K9', $manifestedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotManifestARequested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt))
            ->when(static fn (Shipment $shipment) => $shipment->manifest(TrackingReference::fromString('ACME-4Q7X2K9'), new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itIgnoresAnAlreadyManifestedWithTheSameReference(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $manifestedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt),
                new ShipmentManifested($id, 'ACME-4Q7X2K9', $manifestedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->manifest(TrackingReference::fromString('ACME-4Q7X2K9'), new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itCannotManifestAnAlreadyManifestedWithADifferentReference(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $manifestedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt),
                new ShipmentManifested($id, 'ACME-4Q7X2K9', $manifestedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->manifest(TrackingReference::fromString('ACME-OTHER'), new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(ShipmentAlreadyTrackedException::class);
    }

    #[Test]
    public function itDispatchesManifested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $manifestedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt),
                new ShipmentManifested($id, 'ACME-4Q7X2K9', $manifestedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->dispatch($dispatchedAt))
            ->then(new ShipmentDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotDispatchANotYetManifested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt))
            ->when(static fn (Shipment $shipment) => $shipment->dispatch(new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itIsDeliveredOnceDispatched(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt),
                new ShipmentDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->deliver($deliveredAt))
            ->then(new ShipmentDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotBeDeliveredBeforeBeingDispatched(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt))
            ->when(static fn (Shipment $shipment) => $shipment->deliver(new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itCancelsRequested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt))
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancelled($id, $cancelledAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCancelsPrepared(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $preparedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt),
                new ShipmentPrepared($id, $preparedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancelled($id, $cancelledAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itRejectsCancellationOfAManifested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $manifestedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt),
                new ShipmentManifested($id, 'ACME-4Q7X2K9', $manifestedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancellationRejected($id, ShipmentState::MANIFESTED->value, $cancelledAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itRejectsCancellationOfADispatched(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt),
                new ShipmentDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancellationRejected($id, ShipmentState::DISPATCHED->value, $cancelledAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotCancelAnAlreadyCancelled(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt),
                new ShipmentCancelled($id, $cancelledAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->cancel(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itRejectsCancellationOfADelivered(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $createdAt),
                new ShipmentDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new ShipmentDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancellationRejected($id, ShipmentState::DELIVERED->value, $cancelledAt->format(\DateTimeInterface::ATOM)));
    }

    protected function aggregateClass(): string
    {
        return Shipment::class;
    }

    private static function shippingAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris'));
    }

    private static function shipmentRequested(string $id, string $orderId, string $customerId, \DateTimeImmutable $createdAt): ShipmentRequested
    {
        return new ShipmentRequested(
            $id,
            $orderId,
            $customerId,
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
            $createdAt->format(\DateTimeInterface::ATOM),
        );
    }
}
