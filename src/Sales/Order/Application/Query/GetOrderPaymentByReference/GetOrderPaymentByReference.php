<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\GetOrderPaymentByReference;

use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<OrderPaymentResult>
 */
final readonly class GetOrderPaymentByReference implements QueryInterface
{
    public function __construct(public string $reference)
    {
    }
}
