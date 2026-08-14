<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class LabelAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forFingerprint(string $fingerprint, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('The label fingerprinted "%s" is already used by another key for this identity.', $fingerprint),
            previous: $previous,
        );
    }
}
