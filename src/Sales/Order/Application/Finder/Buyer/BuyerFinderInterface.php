<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Buyer;

interface BuyerFinderInterface
{
    public function ofIdOrNull(string $customerId): ?BuyerResult;
}
