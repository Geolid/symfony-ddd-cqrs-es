<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\Buyer;

interface BuyerFinderInterface
{
    public function ofIdOrNull(string $buyerId): ?BuyerResult;
}
