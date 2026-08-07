<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Order;

use Shared\Application\Finder\FinderInterface;

interface OrderFinderInterface extends FinderInterface
{
    public function ofId(string $id): ?OrderResult;
}
