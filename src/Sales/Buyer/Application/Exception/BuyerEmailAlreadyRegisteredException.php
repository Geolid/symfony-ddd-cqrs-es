<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class BuyerEmailAlreadyRegisteredException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forEmail(string $email, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('Email "%s" is already registered.', $email),
            previous: $previous,
        );
    }
}
