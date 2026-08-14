<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\GetBuyer;

use Sales\Order\Application\Finder\Buyer\BuyerResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<?BuyerResult>
 */
final readonly class GetBuyer implements QueryInterface
{
    public function __construct(public string $customerId)
    {
    }
}
