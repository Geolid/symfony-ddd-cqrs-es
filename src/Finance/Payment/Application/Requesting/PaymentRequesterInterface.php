<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Requesting;

use Finance\Payment\Application\Requesting\Exception\PaymentRequestInProgressException;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface PaymentRequesterInterface
{
    /**
     * @return string the checkout URL the customer should be redirected to
     *
     * @throws PaymentRequestInProgressException
     */
    public function requestFor(string $checkoutSessionId, int $amountInCents, string $currency, string $successUrl, string $cancelUrl, \DateTimeImmutable $expiresAt): string;
}
