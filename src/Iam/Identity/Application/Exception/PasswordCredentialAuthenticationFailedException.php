<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PasswordCredentialAuthenticationFailedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forLogin(string $login): self
    {
        return new self(\sprintf('The login/password pair for "%s" was refused.', $login));
    }
}
