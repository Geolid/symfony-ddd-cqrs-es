<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Repository;

use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Domain\Exception\AggregateNotFoundException;

interface OrderPaymentRepositoryInterface
{
    public function has(OrderPaymentId $id): bool;

    /**
     * @throws AggregateNotFoundException
     */
    public function load(OrderPaymentId $id): OrderPayment;

    public function save(OrderPayment $orderPayment): void;
}
