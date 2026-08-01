<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Repository;

use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\OrderId;

interface OrderRepositoryInterface
{
    public function has(OrderId $id): bool;

    /**
     * @throws OrderNotFoundException
     */
    public function load(OrderId $id): Order;

    public function save(Order $order): void;
}
