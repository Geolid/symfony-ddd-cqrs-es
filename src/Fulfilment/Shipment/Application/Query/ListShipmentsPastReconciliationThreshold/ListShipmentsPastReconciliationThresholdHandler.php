<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\ListShipmentsPastReconciliationThreshold;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryUseCase;
use Shared\Application\Query\Result\StreamResult;

#[QueryUseCase]
final readonly class ListShipmentsPastReconciliationThresholdHandler
{
    public function __construct(
        private ShipmentFinderInterface $shipmentFinder,
        private ClockInterface $clock,
        private int $thresholdHours,
    ) {
    }

    /**
     * @return StreamResult<ShipmentResult>
     */
    public function __invoke(ListShipmentsPastReconciliationThreshold $query): StreamResult
    {
        $cutoff = $this->clock->now()
            ->sub(new \DateInterval(\sprintf('PT%dH', $this->thresholdHours)))
            ->format(\DateTimeInterface::ATOM);

        return new StreamResult(
            $this->shipmentFinder
                ->byStatus(ShipmentStatus::MANIFESTED->value)
                ->manifestedBefore($cutoff),
        );
    }
}
