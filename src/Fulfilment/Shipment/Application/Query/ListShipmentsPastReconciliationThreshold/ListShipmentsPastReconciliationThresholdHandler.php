<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\ListShipmentsPastReconciliationThreshold;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[QueryHandler]
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
            ->sub(new \DateInterval(\sprintf('PT%dH', $this->thresholdHours)));

        return new StreamResult(
            $this->shipmentFinder->stalledBefore($cutoff),
        );
    }
}
