<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain;

use Fulfilment\Shipment\Domain\Event\ShipmentCancellationRejected;
use Fulfilment\Shipment\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipment\Domain\Event\ShipmentCreated;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Event\TrackingReferenceAssigned;
use Fulfilment\Shipment\Domain\Exception\ShipmentCancelledException;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('fulfilment.shipment.shipment')]
final class Shipment implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private ShipmentId $id;
    private string $orderId;
    private string $customerId;
    private string $customerAddress;
    private ?TrackingReference $trackingReference;
    private ShipmentState $status;

    public function id(): ShipmentId
    {
        return $this->id;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function customerAddress(): string
    {
        return $this->customerAddress;
    }

    /**
     * @throws ShipmentCancelledException
     */
    public function ensureNotCancelled(): void
    {
        if ($this->status->isCancelled()) {
            throw ShipmentCancelledException::forId($this->id);
        }
    }

    public static function create(
        ShipmentId $id,
        string $orderId,
        string $customerId,
        string $customerAddress,
        \DateTimeImmutable $createdAt,
    ): self {
        $self = new self();
        $self->recordThat(new ShipmentCreated(
            id: $id->toString(),
            orderId: $orderId,
            customerId: $customerId,
            customerAddress: $customerAddress,
            createdAt: $createdAt->format(\DateTimeInterface::ATOM),
        ));

        return $self;
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function dispatch(\DateTimeImmutable $dispatchedAt): void
    {
        if (!$this->status->isPending()) {
            throw ShipmentInvalidTransitionException::cannotDispatch($this->status);
        }

        $this->recordThat(new ShipmentDispatched(
            id: $this->id->toString(),
            dispatchedAt: $dispatchedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function assignTrackingReference(TrackingReference $trackingReference): void
    {
        if (!$this->status->isDispatched()) {
            throw ShipmentInvalidTransitionException::cannotAssignTrackingReference($this->status);
        }

        if (null !== $this->trackingReference) {
            throw ShipmentInvalidTransitionException::cannotReassignTrackingReference($this->trackingReference->toString());
        }

        $this->recordThat(new TrackingReferenceAssigned(
            id: $this->id->toString(),
            trackingReference: $trackingReference->toString(),
        ));
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function markDelivered(\DateTimeImmutable $deliveredAt): void
    {
        if (!$this->status->isDispatched()) {
            throw ShipmentInvalidTransitionException::cannotMarkDelivered($this->status);
        }

        $this->recordThat(new ShipmentDelivered(
            id: $this->id->toString(),
            deliveredAt: $deliveredAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function cancel(\DateTimeImmutable $cancelledAt): void
    {
        if ($this->status->isCancelled()) {
            return;
        }

        if (!$this->status->isCancellable()) {
            $this->recordThat(new ShipmentCancellationRejected(
                id: $this->id->toString(),
                status: $this->status->value,
                rejectedAt: $cancelledAt->format(\DateTimeInterface::ATOM),
            ));

            return;
        }

        $this->recordThat(new ShipmentCancelled(
            id: $this->id->toString(),
            cancelledAt: $cancelledAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyShipmentCreated(ShipmentCreated $event): void
    {
        $this->id = ShipmentId::fromString($event->id);
        $this->orderId = $event->orderId;
        $this->customerId = $event->customerId;
        $this->customerAddress = $event->customerAddress;
        $this->trackingReference = null;
        $this->status = ShipmentState::PENDING;
    }

    #[Apply]
    private function applyShipmentDispatched(ShipmentDispatched $event): void
    {
        $this->status = ShipmentState::DISPATCHED;
    }

    #[Apply]
    private function applyTrackingReferenceAssigned(TrackingReferenceAssigned $event): void
    {
        $this->trackingReference = TrackingReference::fromString($event->trackingReference);
    }

    #[Apply]
    private function applyShipmentDelivered(ShipmentDelivered $event): void
    {
        $this->status = ShipmentState::DELIVERED;
    }

    #[Apply]
    private function applyShipmentCancelled(ShipmentCancelled $event): void
    {
        $this->status = ShipmentState::CANCELLED;
    }

    #[Apply]
    private function applyShipmentCancellationRejected(ShipmentCancellationRejected $event): void
    {
    }
}
