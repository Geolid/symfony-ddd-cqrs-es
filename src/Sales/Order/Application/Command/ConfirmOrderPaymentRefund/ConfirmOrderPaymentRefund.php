<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\ConfirmOrderPaymentRefund;

use Shared\Application\Command\CommandInterface;

final readonly class ConfirmOrderPaymentRefund implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
