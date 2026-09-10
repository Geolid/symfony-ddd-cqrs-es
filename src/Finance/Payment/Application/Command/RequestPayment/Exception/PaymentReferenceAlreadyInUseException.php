<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\RequestPayment\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PaymentReferenceAlreadyInUseException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forReference(string $reference, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('The payment reference "%s" is already assigned to another payment.', $reference),
            previous: $previous,
        );
    }
}
