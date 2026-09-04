<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\CancelPayment;

use Shared\Application\Command\CommandInterface;

final readonly class CancelPayment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
