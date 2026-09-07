<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Finder\Buyer;

interface BuyerFinderInterface
{
    public function ofIdOrNull(string $buyerId): ?BuyerResult;
}
