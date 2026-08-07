<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class CustomerResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentityId(string $identityId): self
    {
        return new self(\sprintf('No customer is linked to identity "%s".', $identityId));
    }
}
