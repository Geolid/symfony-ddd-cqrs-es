<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\InitiateOrderPaymentRefund;

use Shared\Application\Command\CommandInterface;

final readonly class InitiateOrderPaymentRefund implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
