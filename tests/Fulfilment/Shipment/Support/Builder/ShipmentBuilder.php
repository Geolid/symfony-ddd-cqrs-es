<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Support\Builder;

use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\ClockSequence;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     orderId: string,
 *     customerId: string,
 *     shippingAddress: PostalAddress,
 *     createdAt: \DateTimeImmutable,
 *     trackingReference?: TrackingReference,
 *     returnTrackingReference?: TrackingReference,
 *     returnRejectionReason?: string,
 *     preparedAt?: \DateTimeImmutable,
 *     manifestedAt?: \DateTimeImmutable,
 *     dispatchedAt?: \DateTimeImmutable,
 *     deliveredAt?: \DateTimeImmutable,
 *     cancelledAt?: \DateTimeImmutable,
 *     returnRequestedAt?: \DateTimeImmutable,
 *     returnManifestedAt?: \DateTimeImmutable,
 *     returnDispatchedAt?: \DateTimeImmutable,
 *     returnReceivedAt?: \DateTimeImmutable,
 *     returnApprovedAt?: \DateTimeImmutable,
 *     returnRejectedAt?: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Shipment, Attributes>
 */
final class ShipmentBuilder extends AbstractAggregateBuilder
{
    public function withOrderId(string $orderId): self
    {
        return $this->withAttributes(['orderId' => $orderId]);
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->withAttributes(['customerId' => $customerId]);
    }

    public function withShippingAddress(PostalAddress $shippingAddress): self
    {
        return $this->withAttributes(['shippingAddress' => $shippingAddress]);
    }

    public function withCreatedAt(\DateTimeImmutable $createdAt): self
    {
        return $this->withAttributes(['createdAt' => $createdAt]);
    }

    public function prepared(?\DateTimeImmutable $preparedAt = null): self
    {
        $preparedAt ??= Clock::get()->now();

        return $this->withAttributes(['preparedAt' => $preparedAt])
            ->withModifier(
                static fn (Shipment $shipment) => $shipment->prepare($preparedAt),
            );
    }

    public function manifested(
        ?string $trackingReference = null,
        ?\DateTimeImmutable $manifestedAt = null,
    ): self {
        $trackingReference = TrackingReference::fromString($trackingReference ?? SeededFaker::get()->regexify('ACME-[A-Z0-9]{8}'));
        $manifestedAt ??= Clock::get()->now();

        return $this->withAttributes(['trackingReference' => $trackingReference, 'manifestedAt' => $manifestedAt])
            ->withModifier(
                static fn (Shipment $shipment) => $shipment->manifest($trackingReference, $manifestedAt),
            );
    }

    public function dispatched(?\DateTimeImmutable $dispatchedAt = null): self
    {
        $dispatchedAt ??= Clock::get()->now();

        return $this->withAttributes(['dispatchedAt' => $dispatchedAt])
            ->withModifier(
                static fn (Shipment $shipment) => $shipment->dispatch($dispatchedAt),
            );
    }

    public function delivered(?\DateTimeImmutable $deliveredAt = null): self
    {
        $deliveredAt ??= Clock::get()->now();

        return $this->withAttributes(['deliveredAt' => $deliveredAt])
            ->withModifier(
                static fn (Shipment $shipment) => $shipment->deliver($deliveredAt),
            );
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $cancelledAt ??= Clock::get()->now();

        return $this->withAttributes(['cancelledAt' => $cancelledAt])
            ->withModifier(
                static fn (Shipment $shipment) => $shipment->cancel($cancelledAt),
            );
    }

    public function returnRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        $requestedAt ??= Clock::get()->now();

        return $this->withAttributes(['returnRequestedAt' => $requestedAt])
            ->withModifier(
                static fn (Shipment $shipment) => $shipment->requestReturn($requestedAt),
            );
    }

    public function returnManifested(
        ?string $returnTrackingReference = null,
        ?\DateTimeImmutable $manifestedAt = null,
    ): self {
        $returnTrackingReference = TrackingReference::fromString($returnTrackingReference ?? SeededFaker::get()->regexify('ACME-RETURN-[A-Z0-9]{8}'));
        $manifestedAt ??= Clock::get()->now();

        return $this->withAttributes(['returnTrackingReference' => $returnTrackingReference, 'returnManifestedAt' => $manifestedAt])
            ->withModifier(
                static fn (Shipment $shipment) => $shipment->manifestReturn($returnTrackingReference, $manifestedAt),
            );
    }

    public function returnDispatched(?\DateTimeImmutable $dispatchedAt = null): self
    {
        $dispatchedAt ??= Clock::get()->now();

        return $this->withAttributes(['returnDispatchedAt' => $dispatchedAt])
            ->withModifier(
                static fn (Shipment $shipment) => $shipment->dispatchReturn($dispatchedAt),
            );
    }

    public function returnReceived(?\DateTimeImmutable $receivedAt = null): self
    {
        $receivedAt ??= Clock::get()->now();

        return $this->withAttributes(['returnReceivedAt' => $receivedAt])
            ->withModifier(
                static fn (Shipment $shipment) => $shipment->receiveReturn($receivedAt),
            );
    }

    public function returnApproved(?\DateTimeImmutable $approvedAt = null): self
    {
        $approvedAt ??= Clock::get()->now();

        return $this->withAttributes(['returnApprovedAt' => $approvedAt])
            ->withModifier(
                static fn (Shipment $shipment) => $shipment->approveReturn($approvedAt),
            );
    }

    public function returnRejected(
        ?string $reason = null,
        ?\DateTimeImmutable $rejectedAt = null,
    ): self {
        $reason ??= SeededFaker::get()->sentence(4);
        $rejectedAt ??= Clock::get()->now();

        return $this->withAttributes(['returnRejectionReason' => $reason, 'returnRejectedAt' => $rejectedAt])
            ->withModifier(
                static fn (Shipment $shipment) => $shipment->rejectReturn($reason, $rejectedAt),
            );
    }

    protected function defaults(): array
    {
        return [
            'orderId' => static fn (): string => Uuid::uuid7()->toString(),
            'customerId' => static fn (): string => Uuid::uuid7()->toString(),
            'shippingAddress' => static fn (): PostalAddress => PostalAddress::of(
                FullName::of(SeededFaker::get()->firstName(), SeededFaker::get()->lastName()),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'createdAt' => ClockSequence::next(...),
        ];
    }

    protected function build(): Shipment
    {
        $orderId = $this->attribute('orderId');

        return Shipment::request(
            ShipmentId::forOrder($orderId),
            $orderId,
            $this->attribute('customerId'),
            $this->attribute('shippingAddress'),
            $this->attribute('createdAt'),
        );
    }
}
