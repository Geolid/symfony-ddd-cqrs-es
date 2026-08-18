<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\StartOrderRefund;

use Shared\Application\Command\CommandInterface;

final readonly class StartOrderRefund implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
