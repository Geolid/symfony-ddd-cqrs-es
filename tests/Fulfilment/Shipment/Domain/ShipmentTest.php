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
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\PostalAddress;

final class ShipmentTest extends AggregateRootTestCase
{
    private ShipmentId $id;
    private string $orderId;
    private string $customerId;
    private PostalAddress $shippingAddress;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $preparedAt;
    private TrackingReference $trackingReference;
    private \DateTimeImmutable $manifestedAt;
    private \DateTimeImmutable $dispatchedAt;
    private \DateTimeImmutable $deliveredAt;
    private \DateTimeImmutable $returnRequestedAt;
    private TrackingReference $returnTrackingReference;
    private \DateTimeImmutable $returnManifestedAt;
    private \DateTimeImmutable $returnDispatchedAt;
    private \DateTimeImmutable $returnReceivedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderId = ShipmentBuilder::sample('orderId');
        $this->id = ShipmentId::forOrder($this->orderId);
        $this->customerId = ShipmentBuilder::sample('customerId');
        $this->shippingAddress = ShipmentBuilder::sample('shippingAddress');
        $this->createdAt = ShipmentBuilder::sample('createdAt');
        $this->preparedAt = ShipmentBuilder::sample('preparedAt');
        $this->trackingReference = TrackingReference::fromString('ACME-4Q7X2K9');
        $this->manifestedAt = ShipmentBuilder::sample('manifestedAt');
        $this->dispatchedAt = ShipmentBuilder::sample('dispatchedAt');
        $this->deliveredAt = ShipmentBuilder::sample('deliveredAt');
        $this->returnRequestedAt = ShipmentBuilder::sample('returnRequestedAt');
        $this->returnTrackingReference = TrackingReference::fromString('ACME-RETURN-1');
        $this->returnManifestedAt = ShipmentBuilder::sample('returnManifestedAt');
        $this->returnDispatchedAt = ShipmentBuilder::sample('returnDispatchedAt');
        $this->returnReceivedAt = ShipmentBuilder::sample('returnReceivedAt');
    }

    #[Test]
    public function itRequests(): void
    {
        $this
            ->given()
            ->when(fn (): Shipment => Shipment::request($this->id, $this->orderId, $this->customerId, $this->shippingAddress, $this->createdAt))
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

    #[Test]
    public function itManifestsWhenPrepared(): void
    {
        $this
            ->given($this->requested(), $this->prepared())
            ->when(fn (Shipment $shipment) => $shipment->manifest($this->trackingReference, $this->manifestedAt))
            ->then($this->manifested());
    }

    #[Test]
    public function itDoesNotManifestWhenAlreadyManifestedWithSameReference(): void
    {
        $this
            ->given($this->requested(), $this->manifested())
            ->when(fn (Shipment $shipment) => $shipment->manifest($this->trackingReference, $this->manifestedAt))
            ->then();
    }

    #[Test]
    public function itCannotManifestWhenAlreadyManifestedWithDifferentReference(): void
    {
        $this
            ->given($this->requested(), $this->manifested())
            ->when(static fn (Shipment $shipment) => $shipment->manifest(TrackingReference::fromString('ACME-OTHER'), ShipmentBuilder::sample('manifestedAt')))
            ->expectsException(ShipmentAlreadyTrackedException::class);
    }

    #[Test]
    public function itCannotManifestWhenRequested(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Shipment $shipment) => $shipment->manifest($this->trackingReference, $this->manifestedAt))
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
    public function itRequestsReturnWhenDelivered(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered())
            ->when(fn (Shipment $shipment) => $shipment->requestReturn($this->returnRequestedAt))
            ->then($this->returnRequested());
    }

    #[Test]
    public function itDoesNotRequestReturnWhenAlreadyRequested(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered(), $this->returnRequested())
            ->when(fn (Shipment $shipment) => $shipment->requestReturn($this->returnRequestedAt))
            ->then();
    }

    #[Test]
    public function itCannotRequestReturnWhenNotDelivered(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Shipment $shipment) => $shipment->requestReturn($this->returnRequestedAt))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itManifestsReturnWhenRequested(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered(), $this->returnRequested())
            ->when(fn (Shipment $shipment) => $shipment->manifestReturn($this->returnTrackingReference, $this->returnManifestedAt))
            ->then($this->returnManifested());
    }

    #[Test]
    public function itDoesNotManifestReturnWhenAlreadyManifestedWithSameReference(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered(), $this->returnRequested(), $this->returnManifested())
            ->when(fn (Shipment $shipment) => $shipment->manifestReturn($this->returnTrackingReference, $this->returnManifestedAt))
            ->then();
    }

    #[Test]
    public function itCannotManifestReturnWhenAlreadyManifestedWithDifferentReference(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered(), $this->returnRequested(), $this->returnManifested())
            ->when(static fn (Shipment $shipment) => $shipment->manifestReturn(TrackingReference::fromString('ACME-RETURN-OTHER'), ShipmentBuilder::sample('returnManifestedAt')))
            ->expectsException(ShipmentAlreadyTrackedException::class);
    }

    #[Test]
    public function itCannotManifestReturnWhenNotRequested(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered())
            ->when(fn (Shipment $shipment) => $shipment->manifestReturn($this->returnTrackingReference, $this->returnManifestedAt))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itDispatchesReturnWhenManifested(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered(), $this->returnRequested(), $this->returnManifested())
            ->when(fn (Shipment $shipment) => $shipment->dispatchReturn($this->returnDispatchedAt))
            ->then($this->returnDispatched());
    }

    /**
     * @param list<object> $events
     */
    #[Test]
    #[DataProvider('provideReturnAlreadyDispatchedOrLaterStates')]
    public function itDoesNotDispatchReturnWhenAlreadyDispatched(array $events): void
    {
        $this
            ->given(...$events)
            ->when(fn (Shipment $shipment) => $shipment->dispatchReturn($this->returnDispatchedAt))
            ->then();
    }

    #[Test]
    public function itCannotDispatchReturnWhenNotManifested(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered())
            ->when(fn (Shipment $shipment) => $shipment->dispatchReturn($this->returnDispatchedAt))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itReceivesReturnWhenDispatched(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered(), $this->returnRequested(), $this->returnManifested(), $this->returnDispatched())
            ->when(fn (Shipment $shipment) => $shipment->receiveReturn($this->returnReceivedAt))
            ->then($this->returnReceived());
    }

    #[Test]
    public function itReceivesReturnWhenManifested(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered(), $this->returnRequested(), $this->returnManifested())
            ->when(fn (Shipment $shipment) => $shipment->receiveReturn($this->returnReceivedAt))
            ->then($this->returnReceived());
    }

    /**
     * @param list<object> $events
     */
    #[Test]
    #[DataProvider('provideReturnAlreadyReceivedOrLaterStates')]
    public function itDoesNotReceiveReturnWhenAlreadyReceived(array $events): void
    {
        $this
            ->given(...$events)
            ->when(fn (Shipment $shipment) => $shipment->receiveReturn($this->returnReceivedAt))
            ->then();
    }

    #[Test]
    public function itCannotReceiveReturnWhenNotManifested(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered())
            ->when(fn (Shipment $shipment) => $shipment->receiveReturn($this->returnReceivedAt))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itApprovesReturnWhenReceived(): void
    {
        $approvedAt = ShipmentBuilder::sample('returnApprovedAt');

        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered(), $this->returnReceived())
            ->when(static fn (Shipment $shipment) => $shipment->approveReturn($approvedAt))
            ->then(new ShipmentReturnApproved($this->id->toString(), $approvedAt));
    }

    #[Test]
    public function itDoesNotApproveReturnWhenAlreadyApproved(): void
    {
        $approvedAt = ShipmentBuilder::sample('returnApprovedAt');

        $this
            ->given(
                $this->requested(),
                $this->dispatched(),
                $this->delivered(),
                $this->returnReceived(),
                new ShipmentReturnApproved($this->id->toString(), $approvedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->approveReturn($approvedAt))
            ->then();
    }

    #[Test]
    public function itCannotApproveReturnWhenNotReceived(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered())
            ->when(static fn (Shipment $shipment) => $shipment->approveReturn(ShipmentBuilder::sample('returnApprovedAt')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itRejectsReturnWhenReceived(): void
    {
        $rejectedAt = ShipmentBuilder::sample('returnRejectedAt');

        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered(), $this->returnReceived())
            ->when(static fn (Shipment $shipment) => $shipment->rejectReturn('item damaged beyond resale', $rejectedAt))
            ->then(new ShipmentReturnRejected($this->id->toString(), 'item damaged beyond resale', $rejectedAt));
    }

    #[Test]
    public function itDoesNotRejectReturnWhenAlreadyRejected(): void
    {
        $rejectedAt = ShipmentBuilder::sample('returnRejectedAt');

        $this
            ->given(
                $this->requested(),
                $this->dispatched(),
                $this->delivered(),
                $this->returnReceived(),
                new ShipmentReturnRejected($this->id->toString(), 'item damaged beyond resale', $rejectedAt),
            )
            ->when(static fn (Shipment $shipment) => $shipment->rejectReturn('item damaged beyond resale', $rejectedAt))
            ->then();
    }

    #[Test]
    public function itCannotRejectReturnWhenNotReceived(): void
    {
        $this
            ->given($this->requested(), $this->dispatched(), $this->delivered())
            ->when(static fn (Shipment $shipment) => $shipment->rejectReturn('item damaged beyond resale', ShipmentBuilder::sample('returnRejectedAt')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    /**
     * @return iterable<string, array{0: list<object>}>
     */
    public static function provideReturnAlreadyDispatchedOrLaterStates(): iterable
    {
        $orderId = ShipmentBuilder::sample('orderId');
        $id = ShipmentId::forOrder($orderId)->toString();
        $customerId = ShipmentBuilder::sample('customerId');
        $shippingAddress = self::rawShippingAddress(ShipmentBuilder::sample('shippingAddress'));
        $createdAt = ShipmentBuilder::sample('createdAt');
        $dispatchedAt = ShipmentBuilder::sample('dispatchedAt');
        $deliveredAt = ShipmentBuilder::sample('deliveredAt');
        $returnRequestedAt = ShipmentBuilder::sample('returnRequestedAt');
        $returnManifestedAt = ShipmentBuilder::sample('returnManifestedAt');
        $returnDispatchedAt = ShipmentBuilder::sample('returnDispatchedAt');
        $returnReceivedAt = ShipmentBuilder::sample('returnReceivedAt');
        $returnApprovedAt = ShipmentBuilder::sample('returnApprovedAt');
        $returnRejectedAt = ShipmentBuilder::sample('returnRejectedAt');

        $base = [
            new ShipmentRequested($id, $orderId, $customerId, $shippingAddress, $createdAt),
            new ShipmentDispatched($id, $dispatchedAt),
            new ShipmentDelivered($id, $deliveredAt),
            new ShipmentReturnRequested($id, $returnRequestedAt),
            new ShipmentReturnManifested($id, 'ACME-RETURN-1', $returnManifestedAt),
            new ShipmentReturnDispatched($id, $returnDispatchedAt),
        ];

        yield 'return dispatched' => [$base];
        yield 'return received' => [[...$base, new ShipmentReturnReceived($id, $returnReceivedAt)]];
        yield 'return approved' => [[
            ...$base,
            new ShipmentReturnReceived($id, $returnReceivedAt),
            new ShipmentReturnApproved($id, $returnApprovedAt),
        ]];
        yield 'return rejected' => [[
            ...$base,
            new ShipmentReturnReceived($id, $returnReceivedAt),
            new ShipmentReturnRejected($id, 'item damaged beyond resale', $returnRejectedAt),
        ]];
    }

    /**
     * @return iterable<string, array{0: list<object>}>
     */
    public static function provideReturnAlreadyReceivedOrLaterStates(): iterable
    {
        $orderId = ShipmentBuilder::sample('orderId');
        $id = ShipmentId::forOrder($orderId)->toString();
        $customerId = ShipmentBuilder::sample('customerId');
        $shippingAddress = self::rawShippingAddress(ShipmentBuilder::sample('shippingAddress'));
        $createdAt = ShipmentBuilder::sample('createdAt');
        $dispatchedAt = ShipmentBuilder::sample('dispatchedAt');
        $deliveredAt = ShipmentBuilder::sample('deliveredAt');
        $returnRequestedAt = ShipmentBuilder::sample('returnRequestedAt');
        $returnManifestedAt = ShipmentBuilder::sample('returnManifestedAt');
        $returnReceivedAt = ShipmentBuilder::sample('returnReceivedAt');
        $returnApprovedAt = ShipmentBuilder::sample('returnApprovedAt');
        $returnRejectedAt = ShipmentBuilder::sample('returnRejectedAt');

        $base = [
            new ShipmentRequested($id, $orderId, $customerId, $shippingAddress, $createdAt),
            new ShipmentDispatched($id, $dispatchedAt),
            new ShipmentDelivered($id, $deliveredAt),
            new ShipmentReturnRequested($id, $returnRequestedAt),
            new ShipmentReturnManifested($id, 'ACME-RETURN-1', $returnManifestedAt),
            new ShipmentReturnReceived($id, $returnReceivedAt),
        ];

        yield 'return received' => [$base];
        yield 'return approved' => [[...$base, new ShipmentReturnApproved($id, $returnApprovedAt)]];
        yield 'return rejected' => [[...$base, new ShipmentReturnRejected($id, 'item damaged beyond resale', $returnRejectedAt)]];
    }

    protected function aggregateClass(): string
    {
        return Shipment::class;
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private static function rawShippingAddress(PostalAddress $shippingAddress): array
    {
        return [
            'firstName' => $shippingAddress->fullName->firstName,
            'lastName' => $shippingAddress->fullName->lastName,
            'street' => $shippingAddress->address->street,
            'postalCode' => $shippingAddress->address->postalCode,
            'city' => $shippingAddress->address->city,
            'countryCode' => $shippingAddress->address->countryCode->value,
        ];
    }

    private function requested(): ShipmentRequested
    {
        return new ShipmentRequested(
            $this->id->toString(),
            $this->orderId,
            $this->customerId,
            self::rawShippingAddress($this->shippingAddress),
            $this->createdAt,
        );
    }

    private function prepared(): ShipmentPrepared
    {
        return new ShipmentPrepared($this->id->toString(), $this->preparedAt);
    }

    private function manifested(): ShipmentManifested
    {
        return new ShipmentManifested($this->id->toString(), $this->trackingReference->value, $this->manifestedAt);
    }

    private function dispatched(): ShipmentDispatched
    {
        return new ShipmentDispatched($this->id->toString(), $this->dispatchedAt);
    }

    private function delivered(): ShipmentDelivered
    {
        return new ShipmentDelivered($this->id->toString(), $this->deliveredAt);
    }

    private function returnRequested(): ShipmentReturnRequested
    {
        return new ShipmentReturnRequested($this->id->toString(), $this->returnRequestedAt);
    }

    private function returnManifested(): ShipmentReturnManifested
    {
        return new ShipmentReturnManifested($this->id->toString(), $this->returnTrackingReference->value, $this->returnManifestedAt);
    }

    private function returnDispatched(): ShipmentReturnDispatched
    {
        return new ShipmentReturnDispatched($this->id->toString(), $this->returnDispatchedAt);
    }

    private function returnReceived(): ShipmentReturnReceived
    {
        return new ShipmentReturnReceived($this->id->toString(), $this->returnReceivedAt);
    }
}
