<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RequestEmailChange\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class IdentityEmailAlreadyInUseException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forEmail(string $email): self
    {
        return new self(\sprintf('Email address "%s" is already in use.', $email));
    }
}
