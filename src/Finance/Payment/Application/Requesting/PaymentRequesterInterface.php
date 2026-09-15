<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Requesting;

use Finance\Payment\Application\PSP\PaymentLine;
use Finance\Payment\Application\Requesting\Exception\PaymentRequestInProgressException;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface PaymentRequesterInterface
{
    /**
     * @param list<PaymentLine> $lines
     *
     * @return string the vendor's hosted page URL the customer should be redirected to
     *
     * @throws PaymentRequestInProgressException
     */
    public function requestFor(string $checkoutSessionId, array $lines, string $successUrl, string $cancelUrl, \DateTimeImmutable $expiresAt): string;
}
