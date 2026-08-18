<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\ListOrdersPastReturnWindow;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Sales\Order\Domain\Service\ReturnWindowPolicy;
use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[AsQueryHandler]
final readonly class ListOrdersPastReturnWindowHandler
{
    public function __construct(
        private OrderFinderInterface $orderFinder,
        private ReturnWindowPolicy $returnWindowPolicy,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return StreamResult<OrderResult>
     */
    public function __invoke(ListOrdersPastReturnWindow $query): StreamResult
    {
        $cutoff = $this->returnWindowPolicy->cutoffFor($this->clock->now())->format(\DateTimeInterface::ATOM);

        return new StreamResult($this->orderFinder->deliveredBefore($cutoff));
    }
}
