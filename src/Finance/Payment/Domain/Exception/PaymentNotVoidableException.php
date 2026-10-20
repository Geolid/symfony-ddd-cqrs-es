<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Exception;

use Finance\Payment\Domain\ValueObject\PaymentId;

final class PaymentNotVoidableException extends \DomainException
{
    public static function cannotVoid(PaymentId $id): self
    {
        return new self(\sprintf('Payment "%s" cannot be voided before it is authorized.', $id->toString()));
    }
}
