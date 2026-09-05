<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Command\InitiateRefund;

use Shared\Application\Command\CommandInterface;

final readonly class InitiateRefund implements CommandInterface
{
    public function __construct(public string $orderId)
    {
    }
}
