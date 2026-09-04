<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Query\ListPaymentsPastReconciliationThreshold;

use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\Finder\Payment\PaymentResult;
use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[QueryHandler]
final readonly class ListPaymentsPastReconciliationThresholdHandler
{
    public function __construct(
        private PaymentFinderInterface $orderPaymentFinder,
        private ClockInterface $clock,
        private int $thresholdMinutes,
    ) {
    }

    /**
     * @return StreamResult<PaymentResult>
     */
    public function __invoke(ListPaymentsPastReconciliationThreshold $query): StreamResult
    {
        $cutoff = $this->clock->now()
            ->sub(new \DateInterval(\sprintf('PT%dM', $this->thresholdMinutes)));

        return new StreamResult(
            $this->orderPaymentFinder->stalledBefore($cutoff),
        );
    }
}
