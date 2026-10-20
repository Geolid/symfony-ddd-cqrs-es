<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\Exception;

use Finance\Payment\Domain\ValueObject\PaymentId;

final class PaymentNotFailableException extends \DomainException
{
    public static function cannotFail(PaymentId $id): self
    {
        return new self(\sprintf('Payment "%s" cannot be failed before it is authorized.', $id->toString()));
    }
}
