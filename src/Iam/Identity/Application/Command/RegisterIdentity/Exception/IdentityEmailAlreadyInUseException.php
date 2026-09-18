<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RegisterIdentity\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class IdentityEmailAlreadyInUseException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forEmail(string $email, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('Email address "%s" is already in use.', $email),
            previous: $previous,
        );
    }
}
