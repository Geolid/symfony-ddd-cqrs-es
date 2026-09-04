<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Application\Command\ReceiveWithdrawal;

use Shared\Application\Command\CommandInterface;

final readonly class ReceiveWithdrawal implements CommandInterface
{
    public function __construct(public string $orderId)
    {
    }
}
