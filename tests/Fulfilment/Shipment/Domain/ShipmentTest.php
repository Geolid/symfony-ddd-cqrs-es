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
use Fulfilment\Shipment\Domain\Event\ShipmentReturnApproved;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnDispatched;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnManifested;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnReceived;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnRejected;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnRequested;
use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

final class ShipmentTest extends AggregateRootTestCase
{
    #[Test]
    public function itRequests(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = ShipmentId::forOrder($orderId);
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $customerId = Uuid::uuid7()->toString();

        $this
            ->given()
            ->when(static fn (): Shipment => Shipment::request($id, $orderId, $customerId, self::shippingAddress(), $now))
            ->then(self::shipmentRequested($id->toString(), $orderId, $customerId, $now));
    }

    #[Test]
    public function itPrepares(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $preparedAt = $now->modify('+4 hours');

        $this
            ->given(self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now))
            ->when(static fn (Shipment $shipment) => $shipment->prepare($preparedAt))
            ->then(new ShipmentPrepared($id, $preparedAt));
    }

    #[Test]
    public function itDoesNotPrepareWhenAlreadyPrepared(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $preparedAt = $now->modify('+4 hours');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentPrepared($id, $preparedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->prepare($now->modify('+1 day')))
            ->then();
    }

    #[Test]
    public function itCancelsWhenRequested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = $now->modify('+2 hours');

        $this
            ->given(self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now))
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancelled($id, $cancelledAt));
    }

    #[Test]
    public function itCancelsWhenPrepared(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $preparedAt = $now->modify('+4 hours');
        $cancelledAt = $now->modify('+1 day');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentPrepared($id, $preparedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancelled($id, $cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenAlreadyCancelled(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = $now->modify('+2 hours');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentCancelled($id, $cancelledAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->cancel($now->modify('+1 day')))
            ->then();
    }

    #[Test]
    public function itRejectsCancellationWhenManifested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $manifestedAt = $now->modify('+1 day');
        $cancelledAt = $now->modify('+2 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentManifested($id, 'ACME-4Q7X2K9', $manifestedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancellationRejected($id, ShipmentState::MANIFESTED, $cancelledAt));
    }

    #[Test]
    public function itRejectsCancellationWhenDispatched(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $cancelledAt = $now->modify('+3 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancellationRejected($id, ShipmentState::DISPATCHED, $cancelledAt));
    }

    #[Test]
    public function itRejectsCancellationWhenDelivered(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $cancelledAt = $now->modify('+6 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancellationRejected($id, ShipmentState::DELIVERED, $cancelledAt));
    }

    #[Test]
    public function itManifestsWhenPrepared(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $preparedAt = $now->modify('+4 hours');
        $manifestedAt = $now->modify('+1 day');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentPrepared($id, $preparedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->manifest(TrackingReference::fromString('ACME-4Q7X2K9'), $manifestedAt))
            ->then(new ShipmentManifested($id, 'ACME-4Q7X2K9', $manifestedAt));
    }

    #[Test]
    public function itDoesNotManifestWhenAlreadyManifestedWithSameReference(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $manifestedAt = $now->modify('+1 day');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentManifested($id, 'ACME-4Q7X2K9', $manifestedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->manifest(TrackingReference::fromString('ACME-4Q7X2K9'), $now->modify('+2 days')))
            ->then();
    }

    #[Test]
    public function itCannotManifestWhenAlreadyManifestedWithDifferentReference(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $manifestedAt = $now->modify('+1 day');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentManifested($id, 'ACME-4Q7X2K9', $manifestedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->manifest(TrackingReference::fromString('ACME-OTHER'), $now->modify('+2 days')))
            ->expectsException(ShipmentAlreadyTrackedException::class);
    }

    #[Test]
    public function itCannotManifestWhenRequested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now))
            ->when(static fn (Shipment $shipment) => $shipment->manifest(TrackingReference::fromString('ACME-4Q7X2K9'), $now->modify('+1 day')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itDispatchesWhenManifested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $manifestedAt = $now->modify('+1 day');
        $dispatchedAt = $now->modify('+2 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentManifested($id, 'ACME-4Q7X2K9', $manifestedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->dispatch($dispatchedAt))
            ->then(new ShipmentDispatched($id, $dispatchedAt));
    }

    #[Test]
    public function itDoesNotDispatchWhenAlreadyDispatched(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $manifestedAt = $now->modify('+1 day');
        $dispatchedAt = $now->modify('+2 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentManifested($id, 'ACME-4Q7X2K9', $manifestedAt),
                new ShipmentDispatched($id, $dispatchedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->dispatch($now->modify('+3 days')))
            ->then();
    }

    #[Test]
    public function itCannotDispatchWhenNotManifested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now))
            ->when(static fn (Shipment $shipment) => $shipment->dispatch($now->modify('+1 day')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itDeliversWhenDispatched(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->deliver($deliveredAt))
            ->then(new ShipmentDelivered($id, $deliveredAt));
    }

    #[Test]
    public function itDeliversWhenManifested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $manifestedAt = $now->modify('+1 day');
        $deliveredAt = $now->modify('+4 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentManifested($id, 'ACME-4Q7X2K9', $manifestedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->deliver($deliveredAt))
            ->then(new ShipmentDelivered($id, $deliveredAt));
    }

    #[Test]
    public function itDoesNotDeliverWhenAlreadyDelivered(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->deliver($now->modify('+6 days')))
            ->then();
    }

    #[Test]
    public function itCannotDeliverWhenNotManifested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now))
            ->when(static fn (Shipment $shipment) => $shipment->deliver($now->modify('+1 day')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itRequestsReturnWhenDelivered(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $now->modify('+12 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->requestReturn($requestedAt))
            ->then(new ShipmentReturnRequested($id, $requestedAt));
    }

    #[Test]
    public function itDoesNotRequestReturnWhenAlreadyRequested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $now->modify('+12 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
                new ShipmentReturnRequested($id, $requestedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->requestReturn($now->modify('+13 days')))
            ->then();
    }

    #[Test]
    public function itCannotRequestReturnWhenNotDelivered(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now))
            ->when(static fn (Shipment $shipment) => $shipment->requestReturn($now->modify('+1 day')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itManifestsReturnWhenRequested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $now->modify('+12 days');
        $manifestedAt = $now->modify('+13 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
                new ShipmentReturnRequested($id, $requestedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->manifestReturn(TrackingReference::fromString('ACME-RETURN-1'), $manifestedAt))
            ->then(new ShipmentReturnManifested($id, 'ACME-RETURN-1', $manifestedAt));
    }

    #[Test]
    public function itDoesNotManifestReturnWhenAlreadyManifestedWithSameReference(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $now->modify('+12 days');
        $manifestedAt = $now->modify('+13 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
                new ShipmentReturnRequested($id, $requestedAt),
                new ShipmentReturnManifested($id, 'ACME-RETURN-1', $manifestedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->manifestReturn(TrackingReference::fromString('ACME-RETURN-1'), $now->modify('+14 days')))
            ->then();
    }

    #[Test]
    public function itCannotManifestReturnWhenAlreadyManifestedWithDifferentReference(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $now->modify('+12 days');
        $manifestedAt = $now->modify('+13 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
                new ShipmentReturnRequested($id, $requestedAt),
                new ShipmentReturnManifested($id, 'ACME-RETURN-1', $manifestedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->manifestReturn(TrackingReference::fromString('ACME-RETURN-OTHER'), $now->modify('+14 days')))
            ->expectsException(ShipmentAlreadyTrackedException::class);
    }

    #[Test]
    public function itCannotManifestReturnWhenNotRequested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->manifestReturn(TrackingReference::fromString('ACME-RETURN-1'), $now->modify('+12 days')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itDispatchesReturnWhenManifested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $now->modify('+12 days');
        $manifestedAt = $now->modify('+13 days');
        $returnDispatchedAt = $now->modify('+14 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
                new ShipmentReturnRequested($id, $requestedAt),
                new ShipmentReturnManifested($id, 'ACME-RETURN-1', $manifestedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->dispatchReturn($returnDispatchedAt))
            ->then(new ShipmentReturnDispatched($id, $returnDispatchedAt));
    }

    /**
     * @param list<object> $events
     */
    #[Test]
    #[DataProvider('provideReturnAlreadyDispatchedOrLaterStates')]
    public function itDoesNotDispatchReturnWhenAlreadyDispatched(array $events): void
    {
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(...$events)
            ->when(static fn (Shipment $shipment) => $shipment->dispatchReturn($now->modify('+18 days')))
            ->then();
    }

    #[Test]
    public function itCannotDispatchReturnWhenNotManifested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->dispatchReturn($now->modify('+14 days')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itReceivesReturnWhenDispatched(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $now->modify('+12 days');
        $manifestedAt = $now->modify('+13 days');
        $returnDispatchedAt = $now->modify('+14 days');
        $receivedAt = $now->modify('+16 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
                new ShipmentReturnRequested($id, $requestedAt),
                new ShipmentReturnManifested($id, 'ACME-RETURN-1', $manifestedAt),
                new ShipmentReturnDispatched($id, $returnDispatchedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->receiveReturn($receivedAt))
            ->then(new ShipmentReturnReceived($id, $receivedAt));
    }

    #[Test]
    public function itReceivesReturnWhenManifested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $now->modify('+12 days');
        $manifestedAt = $now->modify('+13 days');
        $receivedAt = $now->modify('+15 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
                new ShipmentReturnRequested($id, $requestedAt),
                new ShipmentReturnManifested($id, 'ACME-RETURN-1', $manifestedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->receiveReturn($receivedAt))
            ->then(new ShipmentReturnReceived($id, $receivedAt));
    }

    /**
     * @param list<object> $events
     */
    #[Test]
    #[DataProvider('provideReturnAlreadyReceivedOrLaterStates')]
    public function itDoesNotReceiveReturnWhenAlreadyReceived(array $events): void
    {
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(...$events)
            ->when(static fn (Shipment $shipment) => $shipment->receiveReturn($now->modify('+17 days')))
            ->then();
    }

    #[Test]
    public function itCannotReceiveReturnWhenNotManifested(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->receiveReturn($now->modify('+12 days')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itApprovesReturnWhenReceived(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $receivedAt = $now->modify('+12 days');
        $approvedAt = $now->modify('+13 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
                new ShipmentReturnReceived($id, $receivedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->approveReturn($approvedAt))
            ->then(new ShipmentReturnApproved($id, $approvedAt));
    }

    #[Test]
    public function itDoesNotApproveReturnWhenAlreadyApproved(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $receivedAt = $now->modify('+12 days');
        $approvedAt = $now->modify('+13 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
                new ShipmentReturnReceived($id, $receivedAt),
                new ShipmentReturnApproved($id, $approvedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->approveReturn($now->modify('+14 days')))
            ->then();
    }

    #[Test]
    public function itCannotApproveReturnWhenNotReceived(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->approveReturn($now->modify('+12 days')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itRejectsReturnWhenReceived(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $receivedAt = $now->modify('+12 days');
        $rejectedAt = $now->modify('+13 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
                new ShipmentReturnReceived($id, $receivedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->rejectReturn('item damaged beyond resale', $rejectedAt))
            ->then(new ShipmentReturnRejected($id, 'item damaged beyond resale', $rejectedAt));
    }

    #[Test]
    public function itDoesNotRejectReturnWhenAlreadyRejected(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $receivedAt = $now->modify('+12 days');
        $rejectedAt = $now->modify('+13 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
                new ShipmentReturnReceived($id, $receivedAt),
                new ShipmentReturnRejected($id, 'item damaged beyond resale', $rejectedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->rejectReturn('item damaged beyond resale', $now->modify('+14 days')))
            ->then();
    }

    #[Test]
    public function itCannotRejectReturnWhenNotReceived(): void
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');

        $this
            ->given(
                self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
                new ShipmentDispatched($id, $dispatchedAt),
                new ShipmentDelivered($id, $deliveredAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->rejectReturn('item damaged beyond resale', $now->modify('+12 days')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    /**
     * @return iterable<string, array{0: list<object>}>
     */
    public static function provideReturnAlreadyDispatchedOrLaterStates(): iterable
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $now->modify('+12 days');
        $manifestedAt = $now->modify('+13 days');
        $returnDispatchedAt = $now->modify('+14 days');
        $receivedAt = $now->modify('+16 days');
        $approvedAt = $now->modify('+17 days');
        $rejectedAt = $now->modify('+17 days');

        $base = [
            self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
            new ShipmentDispatched($id, $dispatchedAt),
            new ShipmentDelivered($id, $deliveredAt),
            new ShipmentReturnRequested($id, $requestedAt),
            new ShipmentReturnManifested($id, 'ACME-RETURN-1', $manifestedAt),
            new ShipmentReturnDispatched($id, $returnDispatchedAt),
        ];

        yield 'return dispatched' => [$base];
        yield 'return received' => [[...$base, new ShipmentReturnReceived($id, $receivedAt)]];
        yield 'return approved' => [[
            ...$base,
            new ShipmentReturnReceived($id, $receivedAt),
            new ShipmentReturnApproved($id, $approvedAt),
        ]];
        yield 'return rejected' => [[
            ...$base,
            new ShipmentReturnReceived($id, $receivedAt),
            new ShipmentReturnRejected($id, 'item damaged beyond resale', $rejectedAt),
        ]];
    }

    /**
     * @return iterable<string, array{0: list<object>}>
     */
    public static function provideReturnAlreadyReceivedOrLaterStates(): iterable
    {
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = $now->modify('+2 days');
        $deliveredAt = $now->modify('+5 days');
        $requestedAt = $now->modify('+12 days');
        $manifestedAt = $now->modify('+13 days');
        $receivedAt = $now->modify('+15 days');
        $approvedAt = $now->modify('+16 days');
        $rejectedAt = $now->modify('+16 days');

        $base = [
            self::shipmentRequested($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), $now),
            new ShipmentDispatched($id, $dispatchedAt),
            new ShipmentDelivered($id, $deliveredAt),
            new ShipmentReturnRequested($id, $requestedAt),
            new ShipmentReturnManifested($id, 'ACME-RETURN-1', $manifestedAt),
            new ShipmentReturnReceived($id, $receivedAt),
        ];

        yield 'return received' => [$base];
        yield 'return approved' => [[...$base, new ShipmentReturnApproved($id, $approvedAt)]];
        yield 'return rejected' => [[...$base, new ShipmentReturnRejected($id, 'item damaged beyond resale', $rejectedAt)]];
    }

    protected function aggregateClass(): string
    {
        return Shipment::class;
    }

    private static function shippingAddress(): PostalAddress
    {
        return PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
    }

    private static function shipmentRequested(string $id, string $orderId, string $customerId, \DateTimeImmutable $createdAt): ShipmentRequested
    {
        return new ShipmentRequested(
            $id,
            $orderId,
            $customerId,
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR'],
            $createdAt,
        );
    }
}
