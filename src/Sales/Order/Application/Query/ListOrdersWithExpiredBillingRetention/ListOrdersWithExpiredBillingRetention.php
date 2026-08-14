<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\ListOrdersWithExpiredBillingRetention;

use Sales\Order\Application\Finder\Order\OrderResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\StreamResult;

/**
 * @implements QueryInterface<StreamResult<OrderResult>>
 */
final readonly class ListOrdersWithExpiredBillingRetention implements QueryInterface
{
    public function __construct(public string $cutoff)
    {
    }
}
