<?php

declare(strict_types=1);

namespace Ordering\Order\Application\Command\PlaceOrder;

use Shared\Application\Command\CommandInterface;

final readonly class PlaceOrder implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $customerId,
        public int $totalAmountInCents,
    ) {
    }
}
