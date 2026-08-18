<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\RefundOrderPayment;

use Shared\Application\Command\CommandInterface;

final readonly class RefundOrderPayment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
