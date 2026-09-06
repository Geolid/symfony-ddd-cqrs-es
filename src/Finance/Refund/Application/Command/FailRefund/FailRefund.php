<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Command\FailRefund;

use Shared\Application\Command\CommandInterface;

final readonly class FailRefund implements CommandInterface
{
    public function __construct(public string $refundId)
    {
    }
}
