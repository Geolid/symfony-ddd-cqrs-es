<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class CustomerEmailAlreadyRegisteredException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forEmail(string $email, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('An email "%s" is already registered.', $email),
            previous: $previous,
        );
    }
}
