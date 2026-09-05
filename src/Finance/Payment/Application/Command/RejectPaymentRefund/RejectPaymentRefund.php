<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\RejectPaymentRefund;

use Shared\Application\Command\CommandInterface;

final readonly class RejectPaymentRefund implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
