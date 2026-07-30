<?php

declare(strict_types=1);

namespace Web\Controller\Criteria;

use Fulfilment\Shipment\Application\Validation\ValidShipmentStatus;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ShipmentCriteria
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Positive]
        public int $itemsPerPage = 10,
        #[ValidShipmentStatus]
        public ?string $status = null,
    ) {
    }
}
