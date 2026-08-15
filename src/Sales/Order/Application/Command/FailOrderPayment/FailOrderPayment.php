<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\FailOrderPayment;

use Shared\Application\Command\CommandInterface;

final readonly class FailOrderPayment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
