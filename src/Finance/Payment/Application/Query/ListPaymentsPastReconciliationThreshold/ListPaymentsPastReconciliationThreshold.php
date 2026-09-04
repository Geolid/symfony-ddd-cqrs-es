<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Query\ListPaymentsPastReconciliationThreshold;

use Finance\Payment\Application\Finder\Payment\PaymentResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\StreamResult;

/**
 * @implements QueryInterface<StreamResult<PaymentResult>>
 */
final readonly class ListPaymentsPastReconciliationThreshold implements QueryInterface
{
}
