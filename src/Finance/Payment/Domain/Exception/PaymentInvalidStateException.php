<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Exception;

use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentState;

final class PaymentInvalidStateException extends \DomainException
{
    public static function cannotCapture(PaymentId $id, PaymentState $state): self
    {
        return new self(\sprintf('Payment "%s" cannot be captured while in state "%s".', $id->toString(), $state->value));
    }

    public static function cannotFail(PaymentId $id, PaymentState $state): self
    {
        return new self(\sprintf('Payment "%s" cannot be failed while in state "%s".', $id->toString(), $state->value));
    }
}
