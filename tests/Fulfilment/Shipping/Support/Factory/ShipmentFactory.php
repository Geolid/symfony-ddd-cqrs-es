<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Support\Factory;

use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipping\Domain\ValueObject\TrackingNumber;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Inputs = array{
 *     id: ShipmentId,
 *     orderId: string,
 *     customerId: string,
 *     origin: PostalAddress,
 *     destination: PostalAddress,
 *     createdAt: \DateTimeImmutable,
 *     preparedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     trackingNumber: TrackingNumber,
 *     manifestedAt: \DateTimeImmutable,
 *     dispatchedAt: \DateTimeImmutable,
 *     deliveredAt: \DateTimeImmutable,
 *     erasureApprovedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<Shipment, Inputs>
 */
final class ShipmentFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return Shipment::class;
    }

    public function withOrderId(string $orderId): self
    {
        return $this->with(['orderId' => $orderId]);
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->with(['customerId' => $customerId]);
    }

    public function withOrigin(PostalAddress $origin): self
    {
        return $this->with(['origin' => $origin]);
    }

    public function withDestination(PostalAddress $destination): self
    {
        return $this->with(['destination' => $destination]);
    }

    public function withCreatedAt(\DateTimeImmutable $createdAt): self
    {
        return $this->with(['createdAt' => $createdAt]);
    }

    public function prepared(?\DateTimeImmutable $preparedAt = null): self
    {
        return $this->with(array_filter(['preparedAt' => $preparedAt]))->transition(
            static function (Shipment $shipment, array $inputs): void {
                $shipment->prepare($inputs['preparedAt']);
            },
        );
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        return $this->with(array_filter(['cancelledAt' => $cancelledAt]))->transition(
            static function (Shipment $shipment, array $inputs): void {
                $shipment->cancel($inputs['cancelledAt']);
            },
        );
    }

    public function manifested(?TrackingNumber $trackingNumber = null, ?\DateTimeImmutable $manifestedAt = null): self
    {
        return $this->with(array_filter([
            'trackingNumber' => $trackingNumber,
            'manifestedAt' => $manifestedAt,
        ]))->transition(
            static function (Shipment $shipment, array $inputs): void {
                $shipment->manifest($inputs['trackingNumber'], $inputs['manifestedAt']);
            },
        );
    }

    public function dispatched(?\DateTimeImmutable $dispatchedAt = null): self
    {
        return $this->with(array_filter(['dispatchedAt' => $dispatchedAt]))->transition(
            static function (Shipment $shipment, array $inputs): void {
                $shipment->dispatch($inputs['dispatchedAt']);
            },
        );
    }

    public function delivered(?\DateTimeImmutable $deliveredAt = null): self
    {
        return $this->with(array_filter(['deliveredAt' => $deliveredAt]))->transition(
            static function (Shipment $shipment, array $inputs): void {
                $shipment->deliver($inputs['deliveredAt']);
            },
        );
    }

    public function erasureApproved(?\DateTimeImmutable $erasureApprovedAt = null): self
    {
        return $this->with(array_filter(['erasureApprovedAt' => $erasureApprovedAt]))->transition(
            static function (Shipment $shipment, array $inputs): void {
                $shipment->approveErasure($inputs['erasureApprovedAt']);
            },
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return Shipment::request(
            $parameters['id'],
            $parameters['orderId'],
            $parameters['customerId'],
            $parameters['origin'],
            $parameters['destination'],
            $parameters['createdAt'],
        );
    }

    protected function initialize(): static
    {
        // The id derives from the FINAL orderId, so a with(['orderId' => ...]) override carries over.
        return parent::initialize()->beforeInstantiate(static function (array $parameters): array {
            Assert::string($parameters['orderId']);
            $parameters['id'] ??= ShipmentId::forOrder($parameters['orderId']);

            return $parameters;
        });
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'orderId' => Uuid::uuid7()->toString(),
            'customerId' => Uuid::uuid7()->toString(),
            'origin' => PostalAddressFactory::new(),
            'destination' => PostalAddressFactory::new(),
            'createdAt' => $now,
            'preparedAt' => $now->modify('+1 day'),
            'cancelledAt' => $now->modify('+2 day'),
            'trackingNumber' => TrackingNumberFactory::new(),
            'manifestedAt' => $now->modify('+3 day'),
            'dispatchedAt' => $now->modify('+4 day'),
            'deliveredAt' => $now->modify('+5 day'),
            'erasureApprovedAt' => $now->modify('+6 day'),
        ];
    }
}
