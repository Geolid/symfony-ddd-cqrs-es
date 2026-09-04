<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Finder\Buyer;

use Sales\Buyer\Application\Exception\BuyerResultNotFoundException;

interface BuyerFinderInterface
{
    /**
     * @throws BuyerResultNotFoundException
     */
    public function ofId(string $id): BuyerResult;
}
