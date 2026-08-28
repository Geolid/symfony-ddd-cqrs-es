<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Specification;

use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;

final readonly class CanTransitionToSpecification
{
    /**
     * @param array<string, list<ShipmentState>> $transitions
     */
    public function __construct(
        private array $transitions,
        private ShipmentState $target,
    ) {
    }

    public function isSatisfiedBy(ShipmentState $candidate): bool
    {
        return \in_array($this->target, $this->transitions[$candidate->value] ?? [], true);
    }
}
