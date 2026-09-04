<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Domain;

use Fulfilment\Shipping\Domain\Event\ShipmentCancellationRejected;
use Fulfilment\Shipping\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipping\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipping\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipping\Domain\Event\ShipmentManifested;
use Fulfilment\Shipping\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipping\Domain\Event\ShipmentRequested;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentState;
use Fulfilment\Shipping\Domain\ValueObject\TrackingNumber;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\PostalAddress;

final class ShipmentTest extends AggregateRootTestCase
{
    private ShipmentId $id;
    private string $reference;
    private string $customerId;
    private PostalAddress $origin;
    private PostalAddress $destination;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $preparedAt;
    private TrackingNumber $trackingNumber;
    private \DateTimeImmutable $manifestedAt;
    private \DateTimeImmutable $dispatchedAt;
    private \DateTimeImmutable $deliveredAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = ShipmentId::generate();
        $this->reference = ShipmentBuilder::sample('reference');
        $this->customerId = ShipmentBuilder::sample('customerId');
        $this->origin = ShipmentBuilder::sample('origin');
        $this->destination = ShipmentBuilder::sample('destination');
        $this->createdAt = ShipmentBuilder::sample('createdAt');
        $this->preparedAt = ShipmentBuilder::sample('preparedAt');
        $this->trackingNumber = TrackingNumber::fromString('ACME-4Q7X2K9');
        $this->manifestedAt = ShipmentBuilder::sample('manifestedAt');
        $this->dispatchedAt = ShipmentBuilder::sample('dispatchedAt');
        $this->deliveredAt = ShipmentBuilder::sample('deliveredAt');
    }

    #[Test]
    public function itRequests(): void
    {
        $this
            ->given()
            ->when(fn (): Shipment => Shipment::request($this->id, $this->reference, $this->customerId, $this->origin, $this->destination, $this->createdAt))
            ->then($this->requested());
    }

    #[Test]
    public function itPrepares(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Shipment $shipment) => $shipment->prepare($this->preparedAt))
            ->then($this->prepared());
    }

    #[Test]
    public function itDoesNotPrepareWhenAlreadyPrepared(): void
    {
        $this
            ->given($this->requested(), $this->prepared())
            ->when(fn (Shipment $shipment) => $shipment->prepare($this->preparedAt))
            ->then();
    }

    #[Test]
    public function itManifestsWhenPrepared(): void
    {
        $this
            ->given($this->requested(), $this->prepared())
            ->when(fn (Shipment $shipment) => $shipment->manifest($this->trackingNumber, $this->manifestedAt))
            ->then($this->manifested());
    }

    #[Test]
    public function itDoesNotManifestWhenAlreadyManifestedWithSameReference(): void
    {
        $this
            ->given($this->requested(), $this->manifested())
            ->when(fn (Shipment $shipment) => $shipment->manifest($this->trackingNumber, $this->manifestedAt))
            ->then();
    }

    #[Test]
    public function itCannotManifestWhenAlreadyManifestedWithDifferentReference(): void
    {
        $this
            ->given($this->requested(), $this->manifested())
            ->when(static fn (Shipment $shipment) => $shipment->manifest(TrackingNumber::fromString('ACME-OTHER'), ShipmentBuilder::sample('manifestedAt')))
            ->expectsException(ShipmentAlreadyTrackedException::class);
    }

    #[Test]
    public function itCannotManifestWhenRequested(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Shipment $shipment) => $shipment->manifest($this->trackingNumber, $this->manifestedAt))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itDispatchesWhenManifested(): void
    {
        $this
            ->given($this->requested(), $this->manifested())
            ->when(fn (Shipment $shipment) => $shipment->dispatch($this->dispatchedAt))
            ->then($this->dispatched());
    }

    #[Test]
    public function itDoesNotDispatchWhenAlreadyDispatched(): void
    {
        $this
            ->given($this->requested(), $this->manifested(), $this->dispatched())
            ->when(fn (Shipment $shipment) => $shipment->dispatch($this->dispatchedAt))
            ->then();
    }

    #[Test]
    public function itCannotDispatchWhenNotManifested(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Shipment $shipment) => $shipment->dispatch($this->dispatchedAt))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itDeliversWhenDispatched(): void
    {
        $this
            ->given($this->requested(), $this->dispatched())
            ->when(fn (Shipment $shipment) => $shipment->deliver($this->deliveredAt))
            ->then($this->delivered());
    }

    #[Test]
    public function itDeliversWhenManifested(): void
    {
        $this
            ->given($this->requested(), $this->manifested())
            ->when(fn (Shipment $shipment) => $shipment->deliver($this->deliveredAt))
            ->then($this->delivered());
    }

    #[Test]
    public function itDoesNotDeliverWhenAlreadyDelivered(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered())
            ->when(fn (Shipment $shipment) => $shipment->deliver($this->deliveredAt))
            ->then();
    }

    #[Test]
    public function itCannotDeliverWhenNotManifested(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Shipment $shipment) => $shipment->deliver($this->deliveredAt))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itCancelsWhenRequested(): void
    {
        $cancelledAt = ShipmentBuilder::sample('cancelledAt');

        $this
            ->given($this->requested())
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancelled($this->id->toString(), $cancelledAt));
    }

    #[Test]
    public function itCancelsWhenPrepared(): void
    {
        $cancelledAt = ShipmentBuilder::sample('cancelledAt');

        $this
            ->given($this->requested(), $this->prepared())
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancelled($this->id->toString(), $cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenAlreadyCancelled(): void
    {
        $cancelledAt = ShipmentBuilder::sample('cancelledAt');

        $this
            ->given($this->requested(), new ShipmentCancelled($this->id->toString(), $cancelledAt))
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then();
    }

    #[Test]
    public function itRejectsCancellationWhenManifested(): void
    {
        $cancelledAt = ShipmentBuilder::sample('cancelledAt');

        $this
            ->given($this->requested(), $this->manifested())
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancellationRejected($this->id->toString(), ShipmentState::MANIFESTED, $cancelledAt));
    }

    #[Test]
    public function itRejectsCancellationWhenDispatched(): void
    {
        $cancelledAt = ShipmentBuilder::sample('cancelledAt');

        $this
            ->given($this->requested(), $this->dispatched())
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancellationRejected($this->id->toString(), ShipmentState::DISPATCHED, $cancelledAt));
    }

    #[Test]
    public function itRejectsCancellationWhenDelivered(): void
    {
        $cancelledAt = ShipmentBuilder::sample('cancelledAt');

        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered())
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancellationRejected($this->id->toString(), ShipmentState::DELIVERED, $cancelledAt));
    }

    protected function aggregateClass(): string
    {
        return Shipment::class;
    }

    private function requested(): ShipmentRequested
    {
        return new ShipmentRequested(
            $this->id->toString(),
            $this->reference,
            $this->customerId,
            $this->toAddressData($this->origin),
            $this->toAddressData($this->destination),
            $this->createdAt,
        );
    }

    private function prepared(): ShipmentPrepared
    {
        return new ShipmentPrepared($this->id->toString(), $this->preparedAt);
    }

    private function manifested(): ShipmentManifested
    {
        return new ShipmentManifested($this->id->toString(), $this->trackingNumber->value, $this->manifestedAt);
    }

    private function dispatched(): ShipmentDispatched
    {
        return new ShipmentDispatched($this->id->toString(), $this->dispatchedAt);
    }

    private function delivered(): ShipmentDelivered
    {
        return new ShipmentDelivered($this->id->toString(), $this->deliveredAt);
    }

    /**
     * @return array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function toAddressData(PostalAddress $address): array
    {
        return [
            'recipientName' => $address->recipientName,
            'street' => $address->address->street,
            'postalCode' => $address->address->postalCode,
            'city' => $address->address->city,
            'countryCode' => $address->address->countryCode->value,
        ];
    }
}
