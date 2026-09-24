<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\IssueTotpCredential\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class TotpAlreadyIssuedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentity(string $identityId, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('Identity "%s" already has a TOTP credential issued.', $identityId),
            previous: $previous,
        );
    }
}
