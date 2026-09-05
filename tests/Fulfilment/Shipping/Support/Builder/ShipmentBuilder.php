<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Support\Builder;

use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentDirection;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipping\Domain\ValueObject\TrackingNumber;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: ShipmentId,
 *     reference: string,
 *     direction: ShipmentDirection,
 *     buyerId: string,
 *     origin: PostalAddress,
 *     destination: PostalAddress,
 *     createdAt: \DateTimeImmutable,
 *     preparedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     trackingNumber: TrackingNumber,
 *     manifestedAt: \DateTimeImmutable,
 *     dispatchedAt: \DateTimeImmutable,
 *     deliveredAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Shipment, Attributes>
 */
final class ShipmentBuilder extends AbstractAggregateBuilder
{
    public function withReference(string $reference): self
    {
        return $this->withAttributes(reference: $reference);
    }

    public function withDirection(ShipmentDirection $direction): self
    {
        return $this->withAttributes(direction: $direction);
    }

    public function withBuyerId(string $buyerId): self
    {
        return $this->withAttributes(buyerId: $buyerId);
    }

    public function withOrigin(PostalAddress $origin): self
    {
        return $this->withAttributes(origin: $origin);
    }

    public function withDestination(PostalAddress $destination): self
    {
        return $this->withAttributes(destination: $destination);
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
        ?string $trackingNumber = null,
        ?\DateTimeImmutable $manifestedAt = null,
    ): self {
        $builder = $this->withAttributes(...array_filter([
            'trackingNumber' => null !== $trackingNumber ? TrackingNumber::fromString($trackingNumber) : null,
            'manifestedAt' => $manifestedAt,
        ]));

        return $builder->withModifier(
            static fn (Shipment $shipment, self $builder) => $shipment->manifest($builder['trackingNumber'], $builder['manifestedAt']),
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

    protected static function defaults(): array
    {
        return [
            'id' => ShipmentId::generate(...),
            'reference' => static fn (): string => Uuid::uuid7()->toString(),
            'direction' => static fn (): ShipmentDirection => ShipmentDirection::OUTBOUND,
            'buyerId' => static fn (): string => Uuid::uuid7()->toString(),
            'origin' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'destination' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'createdAt' => static fn (): \DateTimeImmutable => Clock::get()->now(),
            'preparedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'cancelledAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+2 day'),
            'trackingNumber' => static fn (): TrackingNumber => TrackingNumber::fromString(SeededFaker::get()->unique()->regexify('ACME-[A-Z0-9]{8}')),
            'manifestedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+3 day'),
            'dispatchedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+4 day'),
            'deliveredAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+5 day'),
        ];
    }

    protected function build(): Shipment
    {
        return Shipment::request(
            $this['id'],
            $this['reference'],
            $this['direction'],
            $this['buyerId'],
            $this['origin'],
            $this['destination'],
            $this['createdAt'],
        );
    }
}
