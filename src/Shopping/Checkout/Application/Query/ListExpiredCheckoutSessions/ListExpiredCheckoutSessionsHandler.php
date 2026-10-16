<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Query\ListExpiredCheckoutSessions;

use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\StreamResult;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionResult;

#[QueryHandler]
final readonly class ListExpiredCheckoutSessionsHandler
{
    public function __construct(
        private CheckoutSessionFinderInterface $checkoutSessionFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return StreamResult<CheckoutSessionResult>
     */
    public function __invoke(ListExpiredCheckoutSessions $query): StreamResult
    {
        return new StreamResult(
            $this->checkoutSessionFinder->expiredBefore($this->clock->now()),
        );
    }
}
