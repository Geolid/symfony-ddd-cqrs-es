<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\InitiatePaymentRefund;

use Shared\Application\Command\CommandInterface;

final readonly class InitiatePaymentRefund implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
