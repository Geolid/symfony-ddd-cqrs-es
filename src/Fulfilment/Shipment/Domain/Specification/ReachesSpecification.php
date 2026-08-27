<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\Specification;

use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;

final readonly class ReachesSpecification
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
        return $this->reaches($this->target, $candidate);
    }

    private function reaches(ShipmentState $from, ShipmentState $candidate): bool
    {
        if ($from === $candidate) {
            return true;
        }

        foreach ($this->transitions[$from->value] ?? [] as $next) {
            if ($this->reaches($next, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
