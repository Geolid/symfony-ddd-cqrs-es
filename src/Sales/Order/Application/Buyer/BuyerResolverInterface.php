<?php

declare(strict_types=1);

namespace Sales\Order\Application\Buyer;

interface BuyerResolverInterface
{
    public function resolveFor(string $customerId): ?Buyer;
}
