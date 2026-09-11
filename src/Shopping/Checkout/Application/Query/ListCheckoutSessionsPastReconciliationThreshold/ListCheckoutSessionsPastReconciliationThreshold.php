<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Query\ListCheckoutSessionsPastReconciliationThreshold;

use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\StreamResult;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionResult;

/**
 * @implements QueryInterface<StreamResult<CheckoutSessionResult>>
 */
final readonly class ListCheckoutSessionsPastReconciliationThreshold implements QueryInterface
{
}
