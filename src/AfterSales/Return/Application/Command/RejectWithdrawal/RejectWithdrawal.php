<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Command\RejectWithdrawal;

use Shared\Application\Command\CommandInterface;

final readonly class RejectWithdrawal implements CommandInterface
{
    public function __construct(
        public string $orderId,
        public string $reason,
    ) {
    }
}
