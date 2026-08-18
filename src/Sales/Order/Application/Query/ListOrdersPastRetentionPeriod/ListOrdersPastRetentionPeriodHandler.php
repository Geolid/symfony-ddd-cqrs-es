<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\ListOrdersPastRetentionPeriod;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Sales\Order\Domain\Service\RetentionPolicy;
use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[AsQueryHandler]
final readonly class ListOrdersPastRetentionPeriodHandler
{
    public function __construct(
        private OrderFinderInterface $orderFinder,
        private RetentionPolicy $retentionPolicy,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return StreamResult<OrderResult>
     */
    public function __invoke(ListOrdersPastRetentionPeriod $query): StreamResult
    {
        $cutoff = $this->retentionPolicy->cutoffFor($this->clock->now())->format(\DateTimeInterface::ATOM);

        return new StreamResult($this->orderFinder->closedBefore($cutoff));
    }
}
