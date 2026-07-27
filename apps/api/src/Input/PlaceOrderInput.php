<?php

declare(strict_types=1);

namespace Api\Input;

final readonly class PlaceOrderInput
{
    public function __construct(
        public ?string $customerId = null,
        public ?int $totalAmountInCents = null,
    ) {
    }
}
