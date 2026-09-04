<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Repository;

use Finance\Payment\Domain\Exception\PaymentAlreadyExistsException;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Payment\Domain\Payment;
use Finance\Payment\Domain\ValueObject\PaymentId;

interface PaymentRepositoryInterface
{
    public function has(PaymentId $id): bool;

    /**
     * @throws PaymentNotFoundException
     */
    public function load(PaymentId $id): Payment;

    /**
     * @throws PaymentAlreadyExistsException
     */
    public function save(Payment $orderPayment): void;
}
