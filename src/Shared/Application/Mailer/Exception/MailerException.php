<?php

declare(strict_types=1);

namespace Shared\Application\Mailer\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class MailerException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forRecipient(string $to, ?\Throwable $previous = null): self
    {
        return new self(\sprintf('Failed to send email to "%s".', $to), previous: $previous);
    }
}
