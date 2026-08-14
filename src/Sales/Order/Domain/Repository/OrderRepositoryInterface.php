<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Repository;

use Sales\Order\Domain\Order;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Domain\Exception\AggregateNotFoundException;

interface OrderRepositoryInterface
{
    public function has(OrderId $id): bool;

    /**
     * @throws AggregateNotFoundException
     */
    public function load(OrderId $id): Order;

    public function save(Order $order): void;
}
