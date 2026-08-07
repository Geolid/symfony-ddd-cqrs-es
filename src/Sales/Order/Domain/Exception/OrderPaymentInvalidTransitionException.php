<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Exception;

final class OrderPaymentInvalidTransitionException extends \DomainException
{
    public static function cannotCapture(): self
    {
        return new self('This payment has already been captured.');
    }
}
