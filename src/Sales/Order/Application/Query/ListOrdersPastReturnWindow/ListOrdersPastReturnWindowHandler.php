<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\ListOrdersPastReturnWindow;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Sales\Order\Domain\Service\ReturnWindow;
use Shared\Application\Query\QueryUseCase;
use Shared\Application\Query\Result\StreamResult;

#[QueryUseCase]
final readonly class ListOrdersPastReturnWindowHandler
{
    public function __construct(
        private OrderFinderInterface $orderFinder,
        private ReturnWindow $returnWindow,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return StreamResult<OrderResult>
     */
    public function __invoke(ListOrdersPastReturnWindow $query): StreamResult
    {
        $cutoff = $this->returnWindow->cutoffFor($this->clock->now())->format(\DateTimeInterface::ATOM);

        return new StreamResult($this->orderFinder->deliveredBefore($cutoff));
    }
}
