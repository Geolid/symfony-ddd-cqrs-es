<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Order;

use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Shared\Application\Finder\FinderInterface;

interface OrderFinderInterface extends FinderInterface
{
    /**
     * @throws OrderResultNotFoundException
     */
    public function ofId(string $id): OrderResult;
}
