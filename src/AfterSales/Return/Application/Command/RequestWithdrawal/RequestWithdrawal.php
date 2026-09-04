<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Command\RequestWithdrawal;

use Shared\Application\Command\CommandInterface;

final readonly class RequestWithdrawal implements CommandInterface
{
    public function __construct(
        public string $orderId,
        public string $customerId,
    ) {
    }
}
