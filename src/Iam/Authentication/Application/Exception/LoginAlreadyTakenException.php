<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class LoginAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forLogin(string $login, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('The login "%s" is already in use.', $login),
            previous: $previous,
        );
    }
}
