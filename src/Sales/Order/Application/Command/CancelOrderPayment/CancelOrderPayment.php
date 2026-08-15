<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CancelOrderPayment;

use Shared\Application\Command\CommandInterface;

final readonly class CancelOrderPayment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
