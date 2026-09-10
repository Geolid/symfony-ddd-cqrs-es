<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\ConfirmOrder;

use Shared\Application\Command\CommandInterface;

final readonly class ConfirmOrder implements CommandInterface
{
    /**
     * @param list<array{lineId: string, productId: string, label: string, unitPriceInCents: int, quantity: int}> $lines
     */
    public function __construct(
        public string $id,
        public string $cartId,
        public string $shopperId,
        public string $paymentId,
        public array $lines,
    ) {
    }
}
