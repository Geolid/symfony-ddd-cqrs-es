<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Withdrawal;

use Shared\Application\DrivingPort;

#[DrivingPort]
interface CanRequestWithdrawalInterface
{
    public function forOrder(string $orderId): bool;

    /**
     * @return array<string, bool>
     */
    public function forOrders(string ...$orderIds): array;
}
