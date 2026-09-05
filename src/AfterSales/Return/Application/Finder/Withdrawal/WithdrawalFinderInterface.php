<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Finder\Withdrawal;

use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<WithdrawalResult>
 */
interface WithdrawalFinderInterface extends IterableFinderInterface
{
    public function byOrder(string $orderId): static;

    public function byOrders(string ...$orderIds): static;

    public function active(): static;
}
