<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\ListOrdersPastReturnWindow;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Sales\Order\Domain\Specification\ReturnWindowExpiredSpecification;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[QueryHandler]
final readonly class ListOrdersPastReturnWindowHandler
{
    public function __construct(
        private OrderFinderInterface $orderFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return StreamResult<OrderResult>
     */
    public function __invoke(ListOrdersPastReturnWindow $query): StreamResult
    {
        $cutoff = $this->clock->now()->modify(\sprintf('-%d days', ReturnWindowExpiredSpecification::DAYS));

        return new StreamResult($this->orderFinder->deliveredBefore($cutoff));
    }
}
