<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Query\ListWithdrawalEligibleOrders;

use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<array<string, bool>>
 */
final readonly class ListWithdrawalEligibleOrders implements QueryInterface
{
    /**
     * @param list<string> $orderIds
     */
    public function __construct(public array $orderIds)
    {
    }
}
