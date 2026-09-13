<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Query\ListCheckoutSessionsPastReconciliationThreshold;

use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\StreamResult;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionResult;
use Shopping\Checkout\Domain\CheckoutSession;

#[QueryHandler]
final readonly class ListCheckoutSessionsPastReconciliationThresholdHandler
{
    public function __construct(
        private CheckoutSessionFinderInterface $checkoutSessionFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return StreamResult<CheckoutSessionResult>
     */
    public function __invoke(ListCheckoutSessionsPastReconciliationThreshold $query): StreamResult
    {
        $cutoff = $this->clock->now()
            ->sub(new \DateInterval(\sprintf('PT%dM', CheckoutSession::TTL_MINUTES)));

        return new StreamResult(
            $this->checkoutSessionFinder->stalledBefore($cutoff),
        );
    }
}
