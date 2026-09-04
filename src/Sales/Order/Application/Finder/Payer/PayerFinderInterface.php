<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Payer;

interface PayerFinderInterface
{
    public function ofIdOrNull(string $payerId): ?PayerResult;
}
