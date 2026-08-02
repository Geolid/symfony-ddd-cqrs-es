<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class LoginAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forFingerprint(string $fingerprint): self
    {
        return new self(\sprintf('The login fingerprinted "%s" is already registered.', $fingerprint));
    }
}
