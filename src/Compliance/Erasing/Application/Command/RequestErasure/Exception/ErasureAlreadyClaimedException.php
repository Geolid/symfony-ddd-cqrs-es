<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Command\RequestErasure\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ErasureAlreadyClaimedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentity(string $identityId, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('An erasure has already been requested for identity "%s".', $identityId),
            previous: $previous,
        );
    }
}
