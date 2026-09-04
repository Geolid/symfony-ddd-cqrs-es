<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Query\GetPaymentByReference;

use Finance\Payment\Application\Finder\Payment\PaymentResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<PaymentResult>
 */
final readonly class GetPaymentByReference implements QueryInterface
{
    public function __construct(public string $reference)
    {
    }
}
