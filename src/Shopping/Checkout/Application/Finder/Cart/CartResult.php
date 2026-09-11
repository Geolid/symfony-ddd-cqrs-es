<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\Cart;

final readonly class CartResult
{
    /**
     * @param list<array{lineId: string, productId: string, label: string, unitPriceInCents: int, quantity: int}> $lines
     */
    public function __construct(
        public string $id,
        public string $shopperId,
        public array $lines,
        public int $totalAmountInCents,
    ) {
    }
}
