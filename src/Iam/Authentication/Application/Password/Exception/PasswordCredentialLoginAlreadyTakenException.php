<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Password\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PasswordCredentialLoginAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forLogin(string $login, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('Login "%s" is already in use.', $login),
            previous: $previous,
        );
    }
}
