<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Repository;

use Sales\Ordering\Domain\Order\Exception\OrderAlreadyExistsException;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;

interface OrderRepositoryInterface
{
    public function has(OrderId $id): bool;

    /**
     * @throws OrderNotFoundException
     */
    public function load(OrderId $id): Order;

    /**
     * @throws OrderAlreadyExistsException
     */
    public function save(Order $order): void;
}
