<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\ValueObject;

enum PaymentUniqueKey: string
{
    case REFERENCE = 'finance.payment.payment.reference';
}
