<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class BuyerAddressesNotCompletedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $customerId): self
    {
        return new self(
            message: \sprintf('Buyer "%s" has not completed their shipping and billing addresses yet.', $customerId),
        );
    }
}
