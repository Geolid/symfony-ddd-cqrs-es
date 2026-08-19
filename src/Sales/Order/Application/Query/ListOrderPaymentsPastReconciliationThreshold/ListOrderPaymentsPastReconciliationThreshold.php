<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\ListOrderPaymentsPastReconciliationThreshold;

use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\StreamResult;

/**
 * @implements QueryInterface<StreamResult<OrderPaymentResult>>
 */
final readonly class ListOrderPaymentsPastReconciliationThreshold implements QueryInterface
{
}
