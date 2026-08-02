<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class IdentityResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forLogin(string $login): self
    {
        return new self(\sprintf('No identity carries the login "%s".', $login));
    }
}
