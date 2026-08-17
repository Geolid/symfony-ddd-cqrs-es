<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain;

use Fulfilment\Shipment\Domain\Event\ShipmentCancellationRejected;
use Fulfilment\Shipment\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Event\ShipmentManifested;
use Fulfilment\Shipment\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipment\Domain\Event\ShipmentRequested;
use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyTrackedException;
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
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('fulfilment.shipment.shipment')]
final class Shipment implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private ShipmentId $id;
    private string $orderId;
    private string $customerId;
    private PostalAddress $shippingAddress;
    private ?TrackingReference $trackingReference;
    private ShipmentState $state;

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

    public function shippingAddress(): PostalAddress
    {
        return $this->shippingAddress;
    }

    public static function request(
        ShipmentId $id,
        string $orderId,
        string $customerId,
        PostalAddress $shippingAddress,
        \DateTimeImmutable $createdAt,
    ): self {
        $self = new self();
        $self->recordThat(new ShipmentRequested(
            id: $id->toString(),
            orderId: $orderId,
            customerId: $customerId,
            shippingAddress: [
                'firstName' => $shippingAddress->fullName->firstName,
                'lastName' => $shippingAddress->fullName->lastName,
                'street' => $shippingAddress->address->street,
                'postalCode' => $shippingAddress->address->postalCode,
                'city' => $shippingAddress->address->city,
            ],
            createdAt: $createdAt->format(\DateTimeInterface::ATOM),
        ));

        return $self;
    }

    public function prepare(\DateTimeImmutable $preparedAt): void
    {
        if (!$this->state->isRequested()) {
            return;
        }

        $this->recordThat(new ShipmentPrepared(
            id: $this->id->toString(),
            preparedAt: $preparedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    /**
     * @throws ShipmentAlreadyTrackedException
     * @throws ShipmentInvalidTransitionException
     */
    public function manifest(TrackingReference $trackingReference, \DateTimeImmutable $manifestedAt): void
    {
        if ($this->state->isManifested()) {
            \assert(null !== $this->trackingReference);

            if ($this->trackingReference->equals($trackingReference)) {
                return;
            }

            throw ShipmentAlreadyTrackedException::forReference($this->trackingReference->toString());
        }

        if (!$this->state->isPrepared()) {
            throw ShipmentInvalidTransitionException::cannotManifest($this->state);
        }

        $this->recordThat(new ShipmentManifested(
            id: $this->id->toString(),
            trackingReference: $trackingReference->toString(),
            manifestedAt: $manifestedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function dispatch(\DateTimeImmutable $dispatchedAt): void
    {
        if (!$this->state->isManifested()) {
            throw ShipmentInvalidTransitionException::cannotDispatch($this->state);
        }

        $this->recordThat(new ShipmentDispatched(
            id: $this->id->toString(),
            dispatchedAt: $dispatchedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function deliver(\DateTimeImmutable $deliveredAt): void
    {
        if (!$this->state->isDispatched()) {
            throw ShipmentInvalidTransitionException::cannotDeliver($this->state);
        }

        $this->recordThat(new ShipmentDelivered(
            id: $this->id->toString(),
            deliveredAt: $deliveredAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function cancel(\DateTimeImmutable $cancelledAt): void
    {
        if ($this->state->isCancelled()) {
            return;
        }

        if (!$this->state->isCancellable()) {
            $this->recordThat(new ShipmentCancellationRejected(
                id: $this->id->toString(),
                status: $this->state->value,
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
    private function applyShipmentRequested(ShipmentRequested $event): void
    {
        $this->id = ShipmentId::fromString($event->id);
        $this->orderId = $event->orderId;
        $this->customerId = $event->customerId;
        $this->shippingAddress = PostalAddress::of(
            FullName::of($event->shippingAddress['firstName'], $event->shippingAddress['lastName']),
            Address::of($event->shippingAddress['street'], $event->shippingAddress['postalCode'], $event->shippingAddress['city']),
        );
        $this->trackingReference = null;
        $this->state = ShipmentState::REQUESTED;
    }

    #[Apply]
    private function applyShipmentPrepared(ShipmentPrepared $event): void
    {
        $this->state = ShipmentState::PREPARED;
    }

    #[Apply]
    private function applyShipmentManifested(ShipmentManifested $event): void
    {
        $this->trackingReference = TrackingReference::fromString($event->trackingReference);
        $this->state = ShipmentState::MANIFESTED;
    }

    #[Apply]
    private function applyShipmentDispatched(ShipmentDispatched $event): void
    {
        $this->state = ShipmentState::DISPATCHED;
    }

    #[Apply]
    private function applyShipmentDelivered(ShipmentDelivered $event): void
    {
        $this->state = ShipmentState::DELIVERED;
    }

    #[Apply]
    private function applyShipmentCancelled(ShipmentCancelled $event): void
    {
        $this->state = ShipmentState::CANCELLED;
    }

    #[Apply]
    private function applyShipmentCancellationRejected(ShipmentCancellationRejected $event): void
    {
    }
}
