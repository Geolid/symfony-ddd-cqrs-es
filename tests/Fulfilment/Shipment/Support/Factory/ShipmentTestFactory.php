<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Support\Factory;

use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<Shipment>
 */
final class ShipmentTestFactory extends AbstractAggregateTestFactory
{
    public function withOrderId(string $orderId): self
    {
        return static::new(array_merge($this->attributes, ['orderId' => $orderId]));
    }

    public function withCustomerId(string $customerId): self
    {
        return static::new(array_merge($this->attributes, ['customerId' => $customerId]));
    }

    public function withCustomerAddress(?string $customerAddress): self
    {
        return static::new(array_merge($this->attributes, ['customerAddress' => $customerAddress]));
    }

    public function dispatched(): self
    {
        return $this->withModifier(static fn (Shipment $shipment) => $shipment->dispatch(new \DateTimeImmutable('now +00:00')));
    }

    public function tracked(string $trackingReference): self
    {
        return $this->dispatched()->withModifier(
            static fn (Shipment $shipment) => $shipment->assignTrackingReference(TrackingReference::fromString($trackingReference)),
        );
    }

    public function delivered(): self
    {
        return $this->dispatched()->withModifier(
            static fn (Shipment $shipment) => $shipment->markDelivered(new \DateTimeImmutable('now +00:00')),
        );
    }

    protected function defaults(): array
    {
        return [
            'orderId' => Uuid::uuid7()->toString(),
            'customerId' => Uuid::uuid7()->toString(),
            'customerAddress' => self::faker()->safeEmail(),
            'createdAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): Shipment
    {
        Assert::stringNotEmpty($orderId = $attributes['orderId']);
        Assert::stringNotEmpty($customerId = $attributes['customerId']);
        Assert::nullOrStringNotEmpty($customerAddress = $attributes['customerAddress']);
        Assert::isInstanceOf($createdAt = $attributes['createdAt'], \DateTimeInterface::class);

        return Shipment::create(
            ShipmentId::forOrder($orderId),
            $orderId,
            $customerId,
            $customerAddress,
            \DateTimeImmutable::createFromInterface($createdAt),
        );
    }
}
