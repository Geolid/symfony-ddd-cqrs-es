<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Support\Double;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class StubCarrierGateway implements CarrierGatewayInterface
{
    /**
     * @param array<string, CarrierGatewayStatus> $statusByReference
     */
    public function __construct(
        private array $statusByReference,
        private ?string $failingReference = null,
    ) {
    }

    public function manifest(string $shipmentId, PostalAddress $deliveryAddress): string
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function manifestReturn(string $shipmentId, PostalAddress $pickupAddress): string
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function checkStatus(string $reference): CarrierGatewayStatus
    {
        if ($reference === $this->failingReference) {
            throw new \RuntimeException('Carrier unreachable.');
        }

        return $this->statusByReference[$reference] ?? throw new \LogicException(\sprintf('No stubbed status for reference "%s".', $reference));
    }
}
