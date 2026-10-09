<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Domain;

use Fulfilment\Shipping\Domain\Event\ShipmentCancellationRejected;
use Fulfilment\Shipping\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipping\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipping\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipping\Domain\Event\ShipmentErased;
use Fulfilment\Shipping\Domain\Event\ShipmentErasureApproved;
use Fulfilment\Shipping\Domain\Event\ShipmentManifested;
use Fulfilment\Shipping\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipping\Domain\Event\ShipmentRequested;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentState;
use Fulfilment\Shipping\Domain\ValueObject\TrackingNumber;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentIdFactory;
use Fulfilment\Tests\Shipping\Support\Factory\TrackingNumberFactory;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Symfony\Component\Clock\Clock;

final class ShipmentTest extends AggregateRootTestCase
{
    private ShipmentId $id;
    private string $orderId;
    private string $customerId;
    private PostalAddress $origin;
    private PostalAddress $destination;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $preparedAt;
    private TrackingNumber $trackingNumber;
    private \DateTimeImmutable $manifestedAt;
    private \DateTimeImmutable $dispatchedAt;
    private \DateTimeImmutable $deliveredAt;
    private \DateTimeImmutable $erasureApprovedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = ShipmentIdFactory::new()->create();
        $this->orderId = Uuid::uuid7()->toString();
        $this->customerId = Uuid::uuid7()->toString();
        $this->origin = PostalAddressFactory::new()->create();
        $this->destination = PostalAddressFactory::new()->create();
        $now = Clock::get()->now();
        $this->createdAt = $now;
        $this->preparedAt = $now->modify('+1 day');
        $this->trackingNumber = TrackingNumberFactory::new()->create();
        $this->manifestedAt = $now->modify('+3 day');
        $this->dispatchedAt = $now->modify('+4 day');
        $this->deliveredAt = $now->modify('+5 day');
        $this->erasureApprovedAt = $now->modify('+6 day');
    }

    #[Test]
    public function itRequests(): void
    {
        $this
            ->given()
            ->when(fn (): Shipment => Shipment::request($this->id, $this->orderId, $this->customerId, $this->origin, $this->destination, $this->createdAt))
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
    public function itDoesNotManifestWhenAlreadyManifestedWithSameSourceId(): void
    {
        $this
            ->given($this->requested(), $this->manifested())
            ->when(fn (Shipment $shipment) => $shipment->manifest($this->trackingNumber, $this->manifestedAt))
            ->then();
    }

    #[Test]
    public function itCannotManifestWhenAlreadyManifestedWithDifferentSourceId(): void
    {
        $this
            ->given($this->requested(), $this->manifested())
            ->when(static fn (Shipment $shipment) => $shipment->manifest(TrackingNumber::fromString('ACME-OTHER'), Clock::get()->now()->modify('+3 day')))
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
    public function itDeliversAndErasesWhenErasureApproved(): void
    {
        $this
            ->given($this->requested(), $this->erasureApproved(), $this->dispatched())
            ->when(fn (Shipment $shipment) => $shipment->deliver($this->deliveredAt))
            ->then(
                new ShipmentDelivered($this->id, $this->deliveredAt),
                new ShipmentErased($this->id, $this->deliveredAt),
            );
    }

    #[Test]
    public function itCancelsWhenRequested(): void
    {
        $cancelledAt = Clock::get()->now()->modify('+2 day');

        $this
            ->given($this->requested())
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancelled($this->id, $cancelledAt));
    }

    #[Test]
    public function itCancelsWhenPrepared(): void
    {
        $cancelledAt = Clock::get()->now()->modify('+2 day');

        $this
            ->given($this->requested(), $this->prepared())
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancelled($this->id, $cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenAlreadyCancelled(): void
    {
        $cancelledAt = Clock::get()->now()->modify('+2 day');

        $this
            ->given($this->requested(), new ShipmentCancelled($this->id, $cancelledAt))
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then();
    }

    #[Test]
    public function itRejectsCancellationWhenManifested(): void
    {
        $cancelledAt = Clock::get()->now()->modify('+2 day');

        $this
            ->given($this->requested(), $this->manifested())
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancellationRejected($this->id, ShipmentState::MANIFESTED, $cancelledAt));
    }

    #[Test]
    public function itRejectsCancellationWhenDispatched(): void
    {
        $cancelledAt = Clock::get()->now()->modify('+2 day');

        $this
            ->given($this->requested(), $this->dispatched())
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancellationRejected($this->id, ShipmentState::DISPATCHED, $cancelledAt));
    }

    #[Test]
    public function itRejectsCancellationWhenDelivered(): void
    {
        $cancelledAt = Clock::get()->now()->modify('+2 day');

        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered())
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(new ShipmentCancellationRejected($this->id, ShipmentState::DELIVERED, $cancelledAt));
    }

    #[Test]
    public function itCancelsAndErasesWhenErasureApproved(): void
    {
        $cancelledAt = Clock::get()->now()->modify('+2 day');

        $this
            ->given($this->requested(), $this->erasureApproved())
            ->when(static fn (Shipment $shipment) => $shipment->cancel($cancelledAt))
            ->then(
                new ShipmentCancelled($this->id, $cancelledAt),
                new ShipmentErased($this->id, $cancelledAt),
            );
    }

    #[Test]
    public function itApprovesErasure(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Shipment $shipment) => $shipment->approveErasure($this->erasureApprovedAt))
            ->then(new ShipmentErasureApproved($this->id, $this->erasureApprovedAt));
    }

    #[Test]
    public function itApprovesAndErasesErasureWhenAlreadyDelivered(): void
    {
        $this
            ->given(
                $this->requested(),
                $this->dispatched(),
                new ShipmentDelivered($this->id, $this->deliveredAt),
            )
            ->when(fn (Shipment $shipment) => $shipment->approveErasure($this->erasureApprovedAt))
            ->then(
                new ShipmentErasureApproved($this->id, $this->erasureApprovedAt),
                new ShipmentErased($this->id, $this->erasureApprovedAt),
            );
    }

    #[Test]
    public function itApprovesAndErasesErasureWhenAlreadyCancelled(): void
    {
        $this
            ->given(
                $this->requested(),
                new ShipmentCancelled($this->id, Clock::get()->now()->modify('+2 day')),
            )
            ->when(fn (Shipment $shipment) => $shipment->approveErasure($this->erasureApprovedAt))
            ->then(
                new ShipmentErasureApproved($this->id, $this->erasureApprovedAt),
                new ShipmentErased($this->id, $this->erasureApprovedAt),
            );
    }

    #[Test]
    public function itDoesNotApproveErasureWhenAlreadyApproved(): void
    {
        $this
            ->given($this->requested(), $this->erasureApproved())
            ->when(static fn (Shipment $shipment) => $shipment->approveErasure(Clock::get()->now()->modify('+6 day')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Shipment::class;
    }

    private function requested(): ShipmentRequested
    {
        return new ShipmentRequested(
            $this->id,
            $this->orderId,
            $this->customerId,
            $this->origin,
            $this->destination,
            $this->createdAt,
        );
    }

    private function prepared(): ShipmentPrepared
    {
        return new ShipmentPrepared($this->id, $this->preparedAt);
    }

    private function manifested(): ShipmentManifested
    {
        return new ShipmentManifested($this->id, $this->trackingNumber, $this->manifestedAt);
    }

    private function dispatched(): ShipmentDispatched
    {
        return new ShipmentDispatched($this->id, $this->dispatchedAt);
    }

    private function delivered(): ShipmentDelivered
    {
        return new ShipmentDelivered($this->id, $this->deliveredAt);
    }

    private function erasureApproved(): ShipmentErasureApproved
    {
        return new ShipmentErasureApproved($this->id, $this->erasureApprovedAt);
    }
}
