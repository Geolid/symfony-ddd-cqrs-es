<?php

declare(strict_types=1);

namespace Ordering\Order\Domain\Repository;

use Ordering\Order\Domain\Exception\OrderNotFoundException;
use Ordering\Order\Domain\Order;
use Ordering\Order\Domain\OrderId;

interface OrderRepositoryInterface
{
    public function has(OrderId $id): bool;

    /**
     * @throws OrderNotFoundException
     */
    public function load(OrderId $id): Order;

    public function save(Order $order): void;
}
