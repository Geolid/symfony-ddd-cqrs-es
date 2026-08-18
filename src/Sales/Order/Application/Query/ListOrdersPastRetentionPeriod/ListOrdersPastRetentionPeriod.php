<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\ListOrdersPastRetentionPeriod;

use Sales\Order\Application\Finder\Order\OrderResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\StreamResult;

/**
 * @implements QueryInterface<StreamResult<OrderResult>>
 */
final readonly class ListOrdersPastRetentionPeriod implements QueryInterface
{
}
