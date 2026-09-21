<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\TotpIssuance\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class TotpNotIssuedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('Identity "%s" has no TOTP credential issued.', $identityId));
    }
}
