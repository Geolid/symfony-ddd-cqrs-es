<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Finder\Payment;

use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\PaymentStatus;
use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<PaymentResult>
 */
interface PaymentFinderInterface extends IterableFinderInterface
{
    /**
     * @throws PaymentResultNotFoundException
     */
    public function ofId(string $id): PaymentResult;

    /**
     * @throws PaymentResultNotFoundException
     */
    public function ofReference(string $reference): PaymentResult;

    public function byStatus(PaymentStatus $status): static;

    public function stalledBefore(\DateTimeImmutable $cutoff): static;
}
