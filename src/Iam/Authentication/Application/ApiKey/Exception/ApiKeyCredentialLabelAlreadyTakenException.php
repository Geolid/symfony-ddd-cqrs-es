<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\ApiKey\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ApiKeyCredentialLabelAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forLabel(string $label, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('Label "%s" is already in use.', $label),
            previous: $previous,
        );
    }
}
