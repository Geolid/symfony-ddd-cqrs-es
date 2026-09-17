<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ConfirmIdentityEmail\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class InvalidConfirmationCodeException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('The confirmation code for identity "%s" is invalid.', $id));
    }
}
