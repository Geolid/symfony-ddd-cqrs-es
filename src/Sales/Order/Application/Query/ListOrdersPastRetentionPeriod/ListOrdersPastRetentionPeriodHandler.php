<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\ListOrdersPastRetentionPeriod;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Sales\Order\Domain\Service\RetentionWindow;
use Shared\Application\Query\QueryUseCase;
use Shared\Application\Query\Result\StreamResult;

#[QueryUseCase]
final readonly class ListOrdersPastRetentionPeriodHandler
{
    public function __construct(
        private OrderFinderInterface $orderFinder,
        private RetentionWindow $retentionWindow,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return StreamResult<OrderResult>
     */
    public function __invoke(ListOrdersPastRetentionPeriod $query): StreamResult
    {
        $cutoff = $this->retentionWindow->cutoffFor($this->clock->now())->format(\DateTimeInterface::ATOM);

        return new StreamResult($this->orderFinder->closedBefore($cutoff));
    }
}
