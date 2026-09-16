<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\EnrollTotp\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class TotpAlreadyEnrolledException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentity(string $identityId, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('Identity "%s" already has an active TOTP enrollment.', $identityId),
            previous: $previous,
        );
    }
}
