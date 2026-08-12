<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PaymentReferenceAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forReference(string $reference): self
    {
        return new self(\sprintf('The payment reference "%s" is already assigned to another order payment.', $reference));
    }
}
