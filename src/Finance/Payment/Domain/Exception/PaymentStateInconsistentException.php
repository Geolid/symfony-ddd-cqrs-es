<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Exception;

use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentState;
use Shared\Domain\Exception\AggregateStateInconsistentException;

final class PaymentStateInconsistentException extends AggregateStateInconsistentException
{
    public static function cannotCapture(PaymentId $id, PaymentState $state): self
    {
        return new self(self::describe('Payment', $id->toString(), 'captured', $state->value));
    }

    public static function cannotFail(PaymentId $id, PaymentState $state): self
    {
        return new self(self::describe('Payment', $id->toString(), 'failed', $state->value));
    }

    public static function cannotVoid(PaymentId $id, PaymentState $state): self
    {
        return new self(self::describe('Payment', $id->toString(), 'voided', $state->value));
    }
}
