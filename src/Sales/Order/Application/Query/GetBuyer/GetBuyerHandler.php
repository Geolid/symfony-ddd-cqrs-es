<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\GetBuyer;

use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Order\Application\Finder\Buyer\BuyerResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetBuyerHandler
{
    public function __construct(private BuyerFinderInterface $buyerFinder)
    {
    }

    public function __invoke(GetBuyer $query): ?BuyerResult
    {
        return $this->buyerFinder->ofId($query->customerId);
    }
}
