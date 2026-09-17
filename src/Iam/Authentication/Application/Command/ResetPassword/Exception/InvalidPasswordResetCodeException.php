<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ResetPassword\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class InvalidPasswordResetCodeException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('The password reset code for identity "%s" is invalid.', $identityId));
    }
}
