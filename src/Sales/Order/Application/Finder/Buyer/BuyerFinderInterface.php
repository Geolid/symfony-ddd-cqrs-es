<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Buyer;

use Shared\Application\Finder\FinderInterface;

interface BuyerFinderInterface extends FinderInterface
{
    public function ofId(string $customerId): ?BuyerResult;
}
