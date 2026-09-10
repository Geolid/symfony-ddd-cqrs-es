<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Uniqueness\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ErasureAlreadyRequestedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentity(string $identityId, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('Identity "%s" already has an active erasure request.', $identityId),
            previous: $previous,
        );
    }
}
