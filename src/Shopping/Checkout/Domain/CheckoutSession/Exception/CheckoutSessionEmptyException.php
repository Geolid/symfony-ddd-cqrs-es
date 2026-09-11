<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\CheckoutSession\Exception;

use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionId;

final class CheckoutSessionEmptyException extends \DomainException
{
    public static function forId(CheckoutSessionId $id): self
    {
        return new self(\sprintf('Checkout session "%s" carries no line.', $id->toString()));
    }
}
