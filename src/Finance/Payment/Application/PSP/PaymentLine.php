<?php

declare(strict_types=1);

namespace Finance\Payment\Application\PSP;

use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;

final readonly class PaymentLine
{
    public function __construct(
        public Label $label,
        public Money $unitPrice,
        public Quantity $quantity,
    ) {
    }
}
