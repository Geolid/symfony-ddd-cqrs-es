<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\ListOrderPaymentsPastReconciliationThreshold;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[QueryHandler]
final readonly class ListOrderPaymentsPastReconciliationThresholdHandler
{
    public function __construct(
        private OrderPaymentFinderInterface $orderPaymentFinder,
        private ClockInterface $clock,
        private int $thresholdMinutes,
    ) {
    }

    /**
     * @return StreamResult<OrderPaymentResult>
     */
    public function __invoke(ListOrderPaymentsPastReconciliationThreshold $query): StreamResult
    {
        $cutoff = $this->clock->now()
            ->sub(new \DateInterval(\sprintf('PT%dM', $this->thresholdMinutes)))
            ->format(\DateTimeInterface::ATOM);

        return new StreamResult(
            $this->orderPaymentFinder->stalledBefore($cutoff),
        );
    }
}
