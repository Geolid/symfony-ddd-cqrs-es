<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class CustomerEmailAlreadyRegisteredException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forFingerprint(string $fingerprint, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('An email with fingerprint "%s" is already registered.', $fingerprint),
            previous: $previous,
        );
    }
}
