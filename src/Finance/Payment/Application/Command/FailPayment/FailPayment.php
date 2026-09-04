<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\FailPayment;

use Shared\Application\Command\CommandInterface;

final readonly class FailPayment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
