<?php

declare(strict_types=1);

namespace Api\Input;

use Sales\Order\Application\Validation\ValidMoney;

final readonly class PlaceOrderInput
{
    public function __construct(
        public ?string $customerId = null,
        #[ValidMoney]
        public ?int $totalAmountInCents = null,
    ) {
    }
}
