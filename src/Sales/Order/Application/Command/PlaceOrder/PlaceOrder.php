<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\PlaceOrder;

use Shared\Application\Command\CommandInterface;

final readonly class PlaceOrder implements CommandInterface
{
    /**
     * @param list<array{productId: string, quantity: int, label: string, unitPriceInCents: int}> $lines
     */
    public function __construct(
        public string $id,
        public string $buyerId,
        public array $lines,
    ) {
    }
}
