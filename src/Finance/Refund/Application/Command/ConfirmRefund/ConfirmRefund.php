<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Command\ConfirmRefund;

use Shared\Application\Command\CommandInterface;

final readonly class ConfirmRefund implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
