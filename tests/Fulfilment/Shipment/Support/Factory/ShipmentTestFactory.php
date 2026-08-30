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
use Support\ClockSequence;
use Support\Factory\AbstractAggregateTestFactory;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     orderId: string,
 *     customerId: string,
 *     shippingAddress: PostalAddress,
 *     createdAt: \DateTimeImmutable,
 *     trackingReference?: string,
 *     returnTrackingReference?: string,
 *     returnRejectionReason?: string,
 * }
 *
 * @extends AbstractAggregateTestFactory<Shipment, Attributes>
 */
final class ShipmentTestFactory extends AbstractAggregateTestFactory
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

        return $this->withModifier(
            static fn (Shipment $shipment) => $shipment->prepare($preparedAt),
        );
    }

    public function manifested(
        ?string $trackingReference = null,
        ?\DateTimeImmutable $manifestedAt = null,
    ): self {
        $trackingReference ??= SeededFaker::get()->regexify('ACME-[A-Z0-9]{8}');
        $manifestedAt ??= Clock::get()->now();

        return $this->withAttributes(['trackingReference' => $trackingReference])
            ->withModifier(
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
        ?string $returnTrackingReference = null,
        ?\DateTimeImmutable $manifestedAt = null,
    ): self {
        $returnTrackingReference ??= SeededFaker::get()->regexify('ACME-RETURN-[A-Z0-9]{8}');
        $manifestedAt ??= Clock::get()->now();

        return $this->withAttributes(['returnTrackingReference' => $returnTrackingReference])
            ->withModifier(
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
        ?string $reason = null,
        ?\DateTimeImmutable $rejectedAt = null,
    ): self {
        $reason ??= SeededFaker::get()->sentence(4);
        $rejectedAt ??= Clock::get()->now();

        return $this->withAttributes(['returnRejectionReason' => $reason])
            ->withModifier(
                static fn (Shipment $shipment) => $shipment->rejectReturn($reason, $rejectedAt),
            );
    }

    protected function defaults(): array
    {
        return [
            'orderId' => Uuid::uuid7()->toString(),
            'customerId' => Uuid::uuid7()->toString(),
            'shippingAddress' => PostalAddress::of(
                FullName::of(SeededFaker::get()->firstName(), SeededFaker::get()->lastName()),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'createdAt' => ClockSequence::next(),
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
