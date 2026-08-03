<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\PlaceOrder;

use Shared\Application\Command\CommandInterface;

final readonly class PlaceOrder implements CommandInterface
{
    /**
     * @param list<array{productId: string, quantity: int}> $lines
     */
    public function __construct(
        public string $id,
        public string $customerId,
        public array $lines,
    ) {
    }
}
