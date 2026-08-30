<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Support\Factory;

use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     orderId: string,
 *     customerId: string,
 *     shippingAddress: PostalAddress,
 *     createdAt: \DateTimeInterface,
 * }
 *
 * @extends AbstractAggregateTestFactory<Shipment, Attributes>
 */
final class ShipmentTestFactory extends AbstractAggregateTestFactory
{
    public function withOrderId(string $orderId): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['orderId' => $orderId]));
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['customerId' => $customerId]));
    }

    public function withShippingAddress(PostalAddress $shippingAddress): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['shippingAddress' => $shippingAddress]));
    }

    public function withCreatedAt(\DateTimeImmutable $createdAt): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['createdAt' => $createdAt]));
    }

    public function prepared(?\DateTimeImmutable $preparedAt = null): self
    {
        $preparedAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->prepare($preparedAt),
        );
    }

    public function manifested(
        string $trackingReference = 'ACME-4Q7X2K9',
        ?\DateTimeImmutable $manifestedAt = null,
    ): self {
        $manifestedAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->manifest(TrackingReference::fromString($trackingReference), $manifestedAt),
        );
    }

    public function dispatched(?\DateTimeImmutable $dispatchedAt = null): self
    {
        $dispatchedAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->dispatch($dispatchedAt),
        );
    }

    public function delivered(?\DateTimeImmutable $deliveredAt = null): self
    {
        $deliveredAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->deliver($deliveredAt),
        );
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $cancelledAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->cancel($cancelledAt),
        );
    }

    public function returnRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        $requestedAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->requestReturn($requestedAt),
        );
    }

    public function returnManifested(
        string $returnTrackingReference = 'ACME-RETURN-1',
        ?\DateTimeImmutable $manifestedAt = null,
    ): self {
        $manifestedAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->manifestReturn(TrackingReference::fromString($returnTrackingReference), $manifestedAt),
        );
    }

    public function returnDispatched(?\DateTimeImmutable $dispatchedAt = null): self
    {
        $dispatchedAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->dispatchReturn($dispatchedAt),
        );
    }

    public function returnReceived(?\DateTimeImmutable $receivedAt = null): self
    {
        $receivedAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->receiveReturn($receivedAt),
        );
    }

    public function returnApproved(?\DateTimeImmutable $approvedAt = null): self
    {
        $approvedAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->approveReturn($approvedAt),
        );
    }

    public function returnRejected(
        string $reason = 'item damaged beyond resale',
        ?\DateTimeImmutable $rejectedAt = null,
    ): self {
        $rejectedAt ??= Clock::get()->now();

        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->rejectReturn($reason, $rejectedAt),
        );
    }

    protected function defaults(): array
    {
        return [
            'orderId' => Uuid::uuid7()->toString(),
            'customerId' => Uuid::uuid7()->toString(),
            'shippingAddress' => PostalAddress::of(
                FullName::of(self::faker()->firstName(), self::faker()->lastName()),
                Address::of(self::faker()->streetAddress(), self::faker()->postcode(), self::faker()->city(), self::faker()->countryCode()),
            ),
            'createdAt' => self::nextCreationInstant(),
        ];
    }

    protected function build(array $attributes): Shipment
    {
        Assert::stringNotEmpty($orderId = $attributes['orderId']);
        Assert::stringNotEmpty($customerId = $attributes['customerId']);
        Assert::isInstanceOf($shippingAddress = $attributes['shippingAddress'], PostalAddress::class);
        Assert::isInstanceOf($createdAt = $attributes['createdAt'], \DateTimeInterface::class);

        return Shipment::request(
            ShipmentId::forOrder($orderId),
            $orderId,
            $customerId,
            $shippingAddress,
            \DateTimeImmutable::createFromInterface($createdAt),
        );
    }
}
