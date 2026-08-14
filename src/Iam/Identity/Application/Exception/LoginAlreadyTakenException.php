<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class LoginAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forFingerprint(string $fingerprint, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('The login fingerprinted "%s" is already registered.', $fingerprint),
            previous: $previous,
        );
    }
}
