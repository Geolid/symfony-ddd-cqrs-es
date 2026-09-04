<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Application\Command\ApproveWithdrawal;

use Shared\Application\Command\CommandInterface;

final readonly class ApproveWithdrawal implements CommandInterface
{
    public function __construct(public string $orderId)
    {
    }
}
