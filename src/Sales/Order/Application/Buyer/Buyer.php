<?php

declare(strict_types=1);

namespace Sales\Order\Application\Buyer;

final readonly class Buyer
{
    public function __construct(
        public string $id,
        public string $address,
    ) {
    }
}
