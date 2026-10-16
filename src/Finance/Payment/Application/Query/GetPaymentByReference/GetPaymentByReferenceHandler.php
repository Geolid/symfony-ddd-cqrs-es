<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Query\GetPaymentByReference;

use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\Finder\Payment\PaymentResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetPaymentByReferenceHandler
{
    public function __construct(private PaymentFinderInterface $paymentFinder)
    {
    }

    /**
     * @throws PaymentResultNotFoundException
     */
    public function __invoke(GetPaymentByReference $query): PaymentResult
    {
        return $this->paymentFinder->ofReference($query->reference);
    }
}
