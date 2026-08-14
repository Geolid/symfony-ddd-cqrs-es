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
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<Shipment>
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

    public function dispatched(\DateTimeImmutable $dispatchedAt = new \DateTimeImmutable('now +00:00')): self
    {
        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->dispatch($dispatchedAt),
        );
    }

    public function tracked(string $trackingReference): self
    {
        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->assignTrackingReference(TrackingReference::fromString($trackingReference)),
        );
    }

    public function delivered(\DateTimeImmutable $deliveredAt = new \DateTimeImmutable('now +00:00')): self
    {
        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->markDelivered($deliveredAt),
        );
    }

    public function cancelled(\DateTimeImmutable $cancelledAt = new \DateTimeImmutable('now +00:00')): self
    {
        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->cancel($cancelledAt),
        );
    }

    protected function defaults(): array
    {
        return [
            'orderId' => Uuid::uuid7()->toString(),
            'customerId' => Uuid::uuid7()->toString(),
            'shippingAddress' => PostalAddress::of(
                FullName::of(self::faker()->firstName(), self::faker()->lastName()),
                Address::of(self::faker()->streetAddress(), self::faker()->postcode(), self::faker()->city()),
            ),
            'createdAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): Shipment
    {
        Assert::stringNotEmpty($orderId = $attributes['orderId']);
        Assert::stringNotEmpty($customerId = $attributes['customerId']);
        Assert::isInstanceOf($shippingAddress = $attributes['shippingAddress'], PostalAddress::class);
        Assert::isInstanceOf($createdAt = $attributes['createdAt'], \DateTimeInterface::class);

        return Shipment::create(
            ShipmentId::forOrder($orderId),
            $orderId,
            $customerId,
            $shippingAddress,
            \DateTimeImmutable::createFromInterface($createdAt),
        );
    }
}
