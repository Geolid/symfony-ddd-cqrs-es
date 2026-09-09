<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\VoidPayment;

use Shared\Application\Command\CommandInterface;

final readonly class VoidPayment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
