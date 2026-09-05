<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Query\CanRequestWithdrawal;

use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<bool>
 */
final readonly class CanRequestWithdrawal implements QueryInterface
{
    public function __construct(public string $orderId)
    {
    }
}
