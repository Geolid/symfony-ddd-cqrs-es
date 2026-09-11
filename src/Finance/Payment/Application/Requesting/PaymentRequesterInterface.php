<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Requesting;

use Finance\Payment\Application\Requesting\Exception\PaymentRequestInProgressException;
use Shared\Application\DrivingPort;
use Shared\Domain\ValueObject\PostalAddress;

#[DrivingPort]
interface PaymentRequesterInterface
{
    /**
     * @return string the checkout URL the shopper should be redirected to
     *
     * @throws PaymentRequestInProgressException
     */
    public function requestFor(string $checkoutSessionId, int $amountInCents, PostalAddress $billingAddress, string $returnUrl, \DateTimeImmutable $expiresAt): string;
}
