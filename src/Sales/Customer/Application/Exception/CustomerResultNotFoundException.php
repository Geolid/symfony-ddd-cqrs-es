<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class CustomerResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Customer with ID "%s" not found.', $id));
    }
}
