<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Repository;

use Sales\Ordering\Domain\Exception\OrderAlreadyExistsException;
use Sales\Ordering\Domain\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order;
use Sales\Ordering\Domain\ValueObject\OrderId;

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
