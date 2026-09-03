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
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: ShipmentId,
 *     orderId: string,
 *     customerId: string,
 *     shippingAddress: PostalAddress,
 *     createdAt: \DateTimeImmutable,
 *     preparedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     trackingReference: TrackingReference,
 *     manifestedAt: \DateTimeImmutable,
 *     dispatchedAt: \DateTimeImmutable,
 *     deliveredAt: \DateTimeImmutable,
 *     returnRequestedAt: \DateTimeImmutable,
 *     returnTrackingReference: TrackingReference,
 *     returnManifestedAt: \DateTimeImmutable,
 *     returnDispatchedAt: \DateTimeImmutable,
 *     returnReceivedAt: \DateTimeImmutable,
 *     returnApprovedAt: \DateTimeImmutable,
 *     returnRejectionReason: string,
 *     returnRejectedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Shipment, Attributes>
 */
final class ShipmentBuilder extends AbstractAggregateBuilder
{
    public function withOrderId(string $orderId): self
    {
        return $this->withAttributes(orderId: $orderId);
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->withAttributes(customerId: $customerId);
    }

    public function withShippingAddress(PostalAddress $shippingAddress): self
    {
        return $this->withAttributes(shippingAddress: $shippingAddress);
    }

    public function withCreatedAt(\DateTimeImmutable $createdAt): self
    {
        return $this->withAttributes(createdAt: $createdAt);
    }

    public function prepared(?\DateTimeImmutable $preparedAt = null): self
    {
        $builder = null !== $preparedAt ? $this->withAttributes(preparedAt: $preparedAt) : $this;

        return $builder->withModifier(
            static fn (Shipment $shipment, self $builder) => $shipment->prepare($builder['preparedAt']),
        );
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $builder = null !== $cancelledAt ? $this->withAttributes(cancelledAt: $cancelledAt) : $this;

        return $builder->withModifier(
            static fn (Shipment $shipment, self $builder) => $shipment->cancel($builder['cancelledAt']),
        );
    }

    public function manifested(
        ?string $trackingReference = null,
        ?\DateTimeImmutable $manifestedAt = null,
    ): self {
        $builder = $this->withAttributes(...array_filter([
            'trackingReference' => null !== $trackingReference ? TrackingReference::fromString($trackingReference) : null,
            'manifestedAt' => $manifestedAt,
        ]));

        return $builder->withModifier(
            static fn (Shipment $shipment, self $builder) => $shipment->manifest($builder['trackingReference'], $builder['manifestedAt']),
        );
    }

    public function dispatched(?\DateTimeImmutable $dispatchedAt = null): self
    {
        $builder = null !== $dispatchedAt ? $this->withAttributes(dispatchedAt: $dispatchedAt) : $this;

        return $builder->withModifier(
            static fn (Shipment $shipment, self $builder) => $shipment->dispatch($builder['dispatchedAt']),
        );
    }

    public function delivered(?\DateTimeImmutable $deliveredAt = null): self
    {
        $builder = null !== $deliveredAt ? $this->withAttributes(deliveredAt: $deliveredAt) : $this;

        return $builder->withModifier(
            static fn (Shipment $shipment, self $builder) => $shipment->deliver($builder['deliveredAt']),
        );
    }

    public function returnRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        $builder = null !== $requestedAt ? $this->withAttributes(returnRequestedAt: $requestedAt) : $this;

        return $builder->withModifier(
            static fn (Shipment $shipment, self $builder) => $shipment->requestReturn($builder['returnRequestedAt']),
        );
    }

    public function returnManifested(
        ?string $returnTrackingReference = null,
        ?\DateTimeImmutable $manifestedAt = null,
    ): self {
        $builder = $this->withAttributes(...array_filter([
            'returnTrackingReference' => null !== $returnTrackingReference ? TrackingReference::fromString($returnTrackingReference) : null,
            'returnManifestedAt' => $manifestedAt,
        ]));

        return $builder->withModifier(
            static fn (Shipment $shipment, self $builder) => $shipment->manifestReturn($builder['returnTrackingReference'], $builder['returnManifestedAt']),
        );
    }

    public function returnDispatched(?\DateTimeImmutable $dispatchedAt = null): self
    {
        $builder = null !== $dispatchedAt ? $this->withAttributes(returnDispatchedAt: $dispatchedAt) : $this;

        return $builder->withModifier(
            static fn (Shipment $shipment, self $builder) => $shipment->dispatchReturn($builder['returnDispatchedAt']),
        );
    }

    public function returnReceived(?\DateTimeImmutable $receivedAt = null): self
    {
        $builder = null !== $receivedAt ? $this->withAttributes(returnReceivedAt: $receivedAt) : $this;

        return $builder->withModifier(
            static fn (Shipment $shipment, self $builder) => $shipment->receiveReturn($builder['returnReceivedAt']),
        );
    }

    public function returnApproved(?\DateTimeImmutable $approvedAt = null): self
    {
        $builder = null !== $approvedAt ? $this->withAttributes(returnApprovedAt: $approvedAt) : $this;

        return $builder->withModifier(
            static fn (Shipment $shipment, self $builder) => $shipment->approveReturn($builder['returnApprovedAt']),
        );
    }

    public function returnRejected(
        ?string $reason = null,
        ?\DateTimeImmutable $rejectedAt = null,
    ): self {
        $builder = $this->withAttributes(...array_filter([
            'returnRejectionReason' => $reason,
            'returnRejectedAt' => $rejectedAt,
        ]));

        return $builder->withModifier(
            static fn (Shipment $shipment, self $builder) => $shipment->rejectReturn($builder['returnRejectionReason'], $builder['returnRejectedAt']),
        );
    }

    protected static function defaults(): array
    {
        return [
            'id' => static fn (?self $builder): ShipmentId => ShipmentId::forOrder(
                null !== $builder ? $builder['orderId'] : self::sample('orderId'),
            ),
            'orderId' => static fn (): string => Uuid::uuid7()->toString(),
            'customerId' => static fn (): string => Uuid::uuid7()->toString(),
            'shippingAddress' => static fn (): PostalAddress => PostalAddress::of(
                FullName::of(SeededFaker::get()->firstName(), SeededFaker::get()->lastName()),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'createdAt' => static fn (): \DateTimeImmutable => Clock::get()->now(),
            'preparedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'cancelledAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+2 day'),
            'trackingReference' => static fn (): TrackingReference => TrackingReference::fromString(SeededFaker::get()->unique()->regexify('ACME-[A-Z0-9]{8}')),
            'manifestedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+3 day'),
            'dispatchedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+4 day'),
            'deliveredAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+5 day'),
            'returnRequestedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+6 day'),
            'returnTrackingReference' => static fn (): TrackingReference => TrackingReference::fromString(SeededFaker::get()->unique()->regexify('ACME-RETURN-[A-Z0-9]{8}')),
            'returnManifestedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+7 day'),
            'returnDispatchedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+8 day'),
            'returnReceivedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+9 day'),
            'returnApprovedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+10 day'),
            'returnRejectionReason' => static fn (): string => SeededFaker::get()->sentence(4),
            'returnRejectedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+11 day'),
        ];
    }

    protected function build(): Shipment
    {
        return Shipment::request(
            $this['id'],
            $this['orderId'],
            $this['customerId'],
            $this['shippingAddress'],
            $this['createdAt'],
        );
    }
}
