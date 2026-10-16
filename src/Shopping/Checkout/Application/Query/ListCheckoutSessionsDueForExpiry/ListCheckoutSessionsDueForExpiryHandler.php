<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Query\ListCheckoutSessionsDueForExpiry;

use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\StreamResult;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionResult;

#[QueryHandler]
final readonly class ListCheckoutSessionsDueForExpiryHandler
{
    public function __construct(
        private CheckoutSessionFinderInterface $checkoutSessionFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return StreamResult<CheckoutSessionResult>
     */
    public function __invoke(ListCheckoutSessionsDueForExpiry $query): StreamResult
    {
        return new StreamResult(
            $this->checkoutSessionFinder->dueForExpiryBefore($this->clock->now()),
        );
    }
}
