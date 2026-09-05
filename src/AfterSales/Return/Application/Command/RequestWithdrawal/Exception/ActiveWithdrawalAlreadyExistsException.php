<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Command\RequestWithdrawal\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ActiveWithdrawalAlreadyExistsException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forOrder(string $orderId): self
    {
        return new self(\sprintf('An active withdrawal already exists for order "%s".', $orderId));
    }
}
