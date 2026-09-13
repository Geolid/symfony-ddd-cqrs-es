<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\CheckoutSessionOpening\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class CustomerErasureRequestedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $customerId): self
    {
        return new self(\sprintf('Customer "%s" has an erasure request pending.', $customerId));
    }
}
