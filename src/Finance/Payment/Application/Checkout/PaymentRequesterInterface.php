<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Checkout;

use Finance\Payment\Application\Checkout\Exception\PaymentRequestInProgressException;
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
    public function requestFor(string $cartId, int $amountInCents, PostalAddress $billingAddress, string $returnUrl): string;
}
